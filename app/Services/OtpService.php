<?php

namespace App\Services;

use App\Models\Client;
use App\Models\OtpCode;
use App\Services\Sms\SmsGateway;
use App\Support\Telephone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Codes OTP par SMS : haché en base, 5 minutes, 3 tentatives, 3 envois / 10 minutes, usage unique.
 * Un code n'est émis que pour un client connu : la réponse est identique pour un numéro inconnu.
 */
class OtpService
{
    public const OK = 'ok';

    public const INVALIDE = 'invalide';

    public const EXPIRE = 'expire';

    public const TROP_D_ESSAIS = 'trop_d_essais';

    public function __construct(private readonly SmsGateway $sms) {}

    /**
     * @return bool false si la limite d'envois est atteinte ; true sinon (même si aucun SMS n'est parti
     *              parce que le numéro est inconnu, pour ne pas révéler l'existence du client).
     */
    public function envoyer(string $telephone): bool
    {
        $depuis = now()->subMinutes(10);
        $envois = OtpCode::query()->where('telephone', $telephone)->where('envoye_le', '>', $depuis)->count();
        if ($envois >= config('uam.otp.max_sends_per_10_min')) {
            return false;
        }

        if (! Client::query()->where('telephone', $telephone)->exists()) {
            return true;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($telephone, $code) {
            // Un nouveau code invalide les précédents.
            OtpCode::query()->where('telephone', $telephone)->whereNull('utilise_le')->update(['utilise_le' => now()]);
            OtpCode::query()->create([
                'telephone' => $telephone,
                'code_hash' => Hash::make($code),
                'expire_le' => now()->addMinutes(config('uam.otp.ttl_minutes')),
                'tentatives' => 0,
                'envoye_le' => now(),
            ]);
        });

        $this->sms->envoyer($telephone, __('reservation.sms.otp', [
            'code' => $code,
            'minutes' => config('uam.otp.ttl_minutes'),
        ]));

        return true;
    }

    /** @return self::OK|self::INVALIDE|self::EXPIRE|self::TROP_D_ESSAIS */
    public function verifier(string $telephone, string $code): string
    {
        return DB::transaction(function () use ($telephone, $code) {
            $otp = OtpCode::query()
                ->where('telephone', $telephone)
                ->whereNull('utilise_le')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null) {
                return self::INVALIDE;
            }
            if ($otp->expire_le->isPast()) {
                return self::EXPIRE;
            }
            if ($otp->tentatives >= config('uam.otp.max_attempts')) {
                return self::TROP_D_ESSAIS;
            }

            if (! preg_match('/^\d{6}$/', $code) || ! Hash::check($code, $otp->code_hash)) {
                $otp->increment('tentatives');

                return $otp->tentatives >= config('uam.otp.max_attempts') ? self::TROP_D_ESSAIS : self::INVALIDE;
            }

            // Usage unique : le code ne peut pas être rejoué.
            $otp->forceFill(['utilise_le' => now()])->save();
            Telephone::marquerVerifie($telephone);

            return self::OK;
        });
    }
}
