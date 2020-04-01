<?php


namespace WebEtDesign\CmsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Doctrine\ORM\Mapping\Annotation;

/**
 * @Annotation
 */
class PasswordStrength extends Constraint
{
    public $tooShortMessage = 'Votre mot de passe doit faire au minimum {{ length }} caractères.';
    public $message = 'Mot de passe trop sensible : {{ strength_tips }}';
    public $minLength = 6;
    public $minStrength;
    public $unicodeEquality = false;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'minStrength';
    }

    public function getRequiredOptions()
    {
        return ['minStrength'];
    }
    
    public function getTargets()
    {
        return array(self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT);
    }
}