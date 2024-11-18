<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReturnBaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('buttons', ButtonGroupType::class, [
            'priority' => -1000,
        ]);

        $builder->get('buttons')
            ->add('save', ButtonType::class, [
                'type' => 'submit',
                'label' => 'forms.buttons.save',
            ])
            ->add('mark-as-complete', ButtonType::class, [
                'type' => 'submit',
                'label' => 'forms.buttons.mark-as-complete',
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'forms.buttons.cancel',
                'href' => $options['cancel_url'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('cancel_url')
            ->setAllowedTypes('cancel_url', 'string');

    }
}
