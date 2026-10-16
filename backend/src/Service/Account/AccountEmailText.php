<?php

declare(strict_types=1);

namespace App\Service\Account;

/** Public email copy only. Locale is request-scoped and never stored on an account. */
final class AccountEmailText
{
    public static function locale(string $locale): string
    {
        return in_array($locale, ['en', 'fr', 'es'], true) ? $locale : 'en';
    }

    /** @return array{subject: string, body: string} */
    public static function link(string $purpose, string $locale, string $link): array
    {
        $locale = self::locale($locale);
        $verify = 'verify' === $purpose;
        [$subject, $confirmation, $ending] = match ($locale) {
            'fr' => [
                $verify ? 'Confirmez votre adresse e-mail Chess Openings' : 'Réinitialisez votre mot de passe Chess Openings',
                $verify ? 'Ouvrez ce lien et confirmez votre adresse e-mail, puis connectez-vous avec le mot de passe choisi :' : 'Ouvrez ce lien et choisissez votre nouveau mot de passe :',
                'Ce lien expire dans '.($verify ? '24 heures' : 'une heure')." et ne peut être utilisé qu’une fois. Si vous ne l’avez pas demandé, ignorez cet e-mail.\n",
            ],
            'es' => [
                $verify ? 'Verifica tu correo de Chess Openings' : 'Restablece tu contraseña de Chess Openings',
                $verify ? 'Abre este enlace y confirma tu correo. Después inicia sesión con la contraseña que elegiste:' : 'Abre este enlace y elige tu nueva contraseña:',
                'Este enlace caduca en '.($verify ? '24 horas' : 'una hora')." y solo se puede usar una vez. Si no lo has solicitado, ignora este correo.\n",
            ],
            default => [
                $verify ? 'Verify your Chess Openings email' : 'Reset your Chess Openings password',
                $verify ? 'Open this link and confirm your email, then sign in with the password you chose:' : 'Open this link and choose your new password:',
                'This link expires in '.($verify ? '24 hours' : 'one hour')." and can be used once. If you did not request it, ignore this email.\n",
            ],
        };

        return ['subject' => $subject, 'body' => $subject."\n\n".$confirmation."\n".$link."\n\n".$ending];
    }

    /** @return array{subject: string, body: string} */
    public static function existingAccount(string $locale): array
    {
        return match (self::locale($locale)) {
            'fr' => ['subject' => 'Votre compte Chess Openings existe déjà', 'body' => "Un compte existe déjà pour cette adresse. Connectez-vous ou demandez une réinitialisation du mot de passe depuis la page de connexion.\n\nSi vous n’avez pas demandé cette inscription, ignorez cet e-mail.\n"],
            'es' => ['subject' => 'Tu cuenta de Chess Openings ya existe', 'body' => "Ya existe una cuenta para esta dirección. Inicia sesión o solicita restablecer la contraseña desde la página de inicio de sesión.\n\nSi no has solicitado registrarte, ignora este correo.\n"],
            default => ['subject' => 'Your Chess Openings account already exists', 'body' => "An account already exists for this address. Sign in, or request a password reset from the sign-in page.\n\nIf you did not request registration, ignore this email.\n"],
        };
    }
}
