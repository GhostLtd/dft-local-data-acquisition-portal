<?php

namespace App\Form\Type;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

class BaseButtonsFormType extends AbstractType
{
    const string SAVE = 'save';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('buttons', ButtonGroupType::class, [
            'priority' => -1000,
        ]);

        $builder->get('buttons')
            ->add(self::SAVE, ButtonType::class, [
                'type' => 'submit',
                'label' => 'forms.buttons.save',
                'translation_domain' => 'messages',
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'forms.buttons.cancel',
                'href' => $options['cancel_url'],
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('cancel_url')
            ->setAllowedTypes('cancel_url', 'string');
    }
}
