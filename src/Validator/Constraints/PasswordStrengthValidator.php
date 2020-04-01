<?php

namespace WebEtDesign\CmsBundle\Validator\Constraints;

use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use WebEtDesign\CmsBundle\Validator\Constraints\PasswordStrength;

class PasswordStrengthValidator extends ConstraintValidator
{
    private $translator;
    protected $tips = [];

    private static $levelToLabel = [
        1 => 'very_weak',
        2 => 'weak',
        3 => 'medium',
        4 => 'strong',
        5 => 'very_strong',
    ];

    public function __construct(TranslatorInterface $translator = null)
    {
        // If translator is missing create a new translator.
        // With the 'en' locale and 'validators' domain.
        if (null === $translator) {
            $translator = new Translator('fr');
            $translator->addLoader('xlf', new XliffFileLoader());
            $translator->addResource('xlf', dirname(dirname(__DIR__)) . '/Resources/translations/password_strength.fr.xlf', 'fr', 'validators');
        }

        $this->translator = $translator;
    }

    /**@
     * @param string|null $password
     * @param PasswordStrength|Constraint $constraint
     */
    public function validate($password, $constraint)
    {
        if (null === $password || '' === $password) {
            return;
        }

        if (!is_scalar($password) && !(is_object($password) && method_exists($password, '__toString'))) {
            throw new UnexpectedTypeException($password, 'string');
        }

        $password = (string)$password;
        $passLength = mb_strlen($password);

        if ($passLength < $constraint->minLength) {
            $this->context->buildViolation($constraint->tooShortMessage)
                ->setParameters(['{{ length }}' => $constraint->minLength])
                ->addViolation();

            return;
        }

        dump($this->tips);


        if ($constraint->unicodeEquality) {
            $passwordStrength = $this->calculateStrengthUnicode($password, $constraint->minStrength);
            dump("uni");

            dump($this->tips);

        } else {
            $passwordStrength = $this->calculateStrength($password, $constraint->minStrength);
            dump("not uni");

            dump($this->tips);

        }

        if ($passLength > 12) {
            dump("+");
            ++$passwordStrength;
        } else {
            $this->tips[] = 'length';
        }

        dump($this->tips);

        // There is no decrease of strength on weak combinations.
        // Detecting this is tricky and requires a deep understanding of the syntax.

        if ($passwordStrength < $constraint->minStrength) {
            $parameters = [
                '{{ length }}' => $constraint->minLength,
                '{{ min_strength }}' => $this->translator->trans(/* @Ignore */ 'strength_password.strength_level.' . self::$levelToLabel[$constraint->minStrength], [], 'validators'),
                '{{ current_strength }}' => $this->translator->trans(/* @Ignore */ 'strength_password.strength_level.' . self::$levelToLabel[$passwordStrength], [], 'validators'),
                '{{ strength_tips }}' => implode(', ', array_map([$this, 'translateTips'], $this->tips)),
            ];

            $this->context->buildViolation($constraint->message)
                ->setParameters($parameters)
                ->addViolation();
        }
    }

    /**
     * @internal
     */
    public function translateTips($tip)
    {
        return $this->translator->trans(/* @Ignore */ 'strength_password.tip.' . $tip, [], 'validators');
    }

    private function calculateStrength($password, $minStrength)
    {
        $passwordStrength = 0;

        if (preg_match('/[a-zA-Z]/', $password)) {
            ++$passwordStrength;

            if (!preg_match('/[a-z]/', $password)) {
                $tips[] = 'lowercase_letters';
            } elseif (preg_match('/[A-Z]/', $password)) {

                ++$passwordStrength;
            } else {
                $this->tips[] = 'uppercase_letters';
            }
        } else {
            $this->tips[] = 'letters';
        }

        if (preg_match('/\d+/', $password)) {
            ++$passwordStrength;
        } else {
            $this->tips[] = 'numbers';
        }

        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            ++$passwordStrength;
        } else {
            $this->tips[] = 'special_chars';

        }

        return $passwordStrength;
    }

    private function calculateStrengthUnicode($password, $minStrength)
    {
        $passwordStrength = 0;

        if (preg_match('/\p{L}/u', $password)) {
            ++$passwordStrength;

            if (!preg_match('/\p{Ll}/u', $password)) {
                $this->tips[] = 'lowercase_letters';
            } elseif (preg_match('/\p{Lu}/u', $password)) {
                ++$passwordStrength;
            } else {
                $this->tips[] = 'uppercase_letters';
            }
        } else {
            $this->tips[] = 'letters';
        }

        if (preg_match('/\p{N}/u', $password)) {
            ++$passwordStrength;
        } else {
            $this->tips[] = 'numbers';
        }

        if (preg_match('/[^\p{L}\p{N}]/u', $password)) {
            ++$passwordStrength;
        } else {
            $this->tips[] = 'special_chars';
        }

        return $passwordStrength;
    }
}