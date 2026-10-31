<?php

use App\Models\Client;
use App\Models\OtpCode;
use App\Services\OtpService;
use App\Services\Sms\SmsGateway;
use App\Support\Telephone;
use Tests\Support\SmsCapture;

beforeEach(function () {
    $this->sms = new SmsCapture;
    app()->instance(SmsGateway::class, $this->sms);
    $this->otp = app(OtpService::class);
    $this->client = Client::factory()->create(['telephone' => '+22796000000']);
    $this->tel = '+22796000000';
});

it('envoie un code à 6 chiffres et ne le stocke que haché', function () {
    expect($this->otp->envoyer($this->tel))->toBeTrue();

    $code = $this->sms->dernierCode();
    $enregistre = OtpCode::first();

    expect($code)->toMatch('/^\d{6}$/')
        ->and($this->sms->dernier()['telephone'])->toBe($this->tel)
        ->and($enregistre->code_hash)->not->toBe($code)
        ->and($enregistre->code_hash)->not->toContain($code)
        ->and($enregistre->expire_le->diffInMinutes(now(), true))->toBeLessThanOrEqual(5);
});

it('valide le bon code et marque le téléphone comme vérifié', function () {
    $this->otp->envoyer($this->tel);

    expect(Telephone::estVerifie($this->tel))->toBeFalse()
        ->and($this->otp->verifier($this->tel, $this->sms->dernierCode()))->toBe(OtpService::OK)
        ->and(Telephone::estVerifie($this->tel))->toBeTrue();
});

it('ne laisse pas rejouer un code déjà utilisé', function () {
    $this->otp->envoyer($this->tel);
    $code = $this->sms->dernierCode();

    expect($this->otp->verifier($this->tel, $code))->toBe(OtpService::OK)
        ->and($this->otp->verifier($this->tel, $code))->toBe(OtpService::INVALIDE);
});

it('bloque le code après 3 tentatives, même avec le bon code ensuite', function () {
    $this->otp->envoyer($this->tel);
    $code = $this->sms->dernierCode();
    $faux = $code === '000000' ? '111111' : '000000';

    expect($this->otp->verifier($this->tel, $faux))->toBe(OtpService::INVALIDE)
        ->and($this->otp->verifier($this->tel, $faux))->toBe(OtpService::INVALIDE)
        ->and($this->otp->verifier($this->tel, $faux))->toBe(OtpService::TROP_D_ESSAIS)
        ->and($this->otp->verifier($this->tel, $code))->toBe(OtpService::TROP_D_ESSAIS)
        ->and(Telephone::estVerifie($this->tel))->toBeFalse();
});

it('expire le code après 5 minutes', function () {
    $this->otp->envoyer($this->tel);
    $code = $this->sms->dernierCode();

    $this->travel(6)->minutes();

    expect($this->otp->verifier($this->tel, $code))->toBe(OtpService::EXPIRE);
});

it('limite à 3 envois par tranche de 10 minutes puis relâche', function () {
    expect($this->otp->envoyer($this->tel))->toBeTrue()
        ->and($this->otp->envoyer($this->tel))->toBeTrue()
        ->and($this->otp->envoyer($this->tel))->toBeTrue()
        ->and($this->otp->envoyer($this->tel))->toBeFalse()
        ->and($this->sms->envoyes)->toHaveCount(3);

    $this->travel(11)->minutes();

    expect($this->otp->envoyer($this->tel))->toBeTrue();
});

it('invalide l\'ancien code quand un nouveau est envoyé', function () {
    $this->otp->envoyer($this->tel);
    $ancien = $this->sms->dernierCode();
    do {
        $this->otp->envoyer($this->tel);
        $nouveau = $this->sms->dernierCode();
    } while ($nouveau === $ancien && count($this->sms->envoyes) < 3);

    if ($nouveau !== $ancien) {
        expect($this->otp->verifier($this->tel, $ancien))->toBe(OtpService::INVALIDE);
    }
    expect(true)->toBeTrue();
});

it('n\'envoie aucun SMS pour un numéro inconnu mais répond comme pour un connu', function () {
    expect($this->otp->envoyer('+22790111111'))->toBeTrue()
        ->and($this->sms->envoyes)->toBeEmpty()
        ->and(OtpCode::count())->toBe(0);
});

it('expose l\'API OTP sans révéler l\'identité avant validation', function () {
    $this->postJson('/api/otp/send', ['telephone' => '96 00 00 00'])->assertOk()->assertExactJson(['sent' => true]);
    $inconnu = $this->postJson('/api/otp/send', ['telephone' => '90 11 11 11'])->assertOk()->assertExactJson(['sent' => true]);

    $this->postJson('/api/otp/verify', ['telephone' => $this->tel, 'code' => '000000'])
        ->assertStatus(422)
        ->assertJsonMissingPath('identite')
        ->assertJsonPath('verified', false);

    $this->postJson('/api/otp/verify', ['telephone' => $this->tel, 'code' => $this->sms->dernierCode()])
        ->assertOk()
        ->assertJsonPath('verified', true)
        ->assertJsonPath('identite.nom', $this->client->nom);
});

it('limite les envois d\'OTP par route', function () {
    foreach (range(1, 3) as $i) {
        $this->postJson('/api/otp/send', ['telephone' => '96 00 00 00'])->assertOk();
    }

    $this->postJson('/api/otp/send', ['telephone' => '96 00 00 00'])->assertStatus(429);
});
