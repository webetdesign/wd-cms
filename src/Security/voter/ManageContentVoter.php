<?php

namespace WebEtDesign\CmsBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter as Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author benjamin
 * @create 19/08/2021
 */
class ManageContentVoter extends Voter
{
    const CAN_MANAGE_CONTENT = 'CAN_MANAGE_CONTENT';

    protected function supports(string $attribute, $subject)
    {
        return $subject instanceof UserInterface && $attribute === self::CAN_MANAGE_CONTENT;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            // the user must be logged in; if not, deny access
            return false;
        }

        return $user->hasRole('ROLE_ADMIN_CMS');
    }
}
