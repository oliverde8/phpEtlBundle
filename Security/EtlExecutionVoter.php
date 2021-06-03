<?php

namespace Oliverde8\PhpEtlBundle\Security;

use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EtlExecutionVoter extends Voter
{
    const QUEUE = 'queue';
    const VIEW = 'view';
    const DASHBOARD = 'dashboard';
    const DOWNLOAD = 'download';

    protected function supports($attribute, $subject)
    {
        if ($subject == EtlExecution::class) {
            return true;
        }

        if ($subject instanceof EtlExecution) {
            return true;
        }

        return false;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        return true;
    }
}
