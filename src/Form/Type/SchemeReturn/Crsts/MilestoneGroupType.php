<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MilestoneGroupType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'compound' => true,
            'label' => false,
            'attr' => ['class' => 'ldap-milestone-group'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'ldap_milestone_group';
    }
}
