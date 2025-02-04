<?php

namespace App\Form\Type\Admin;

use App\Entity\Authority;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthorityType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', Gds\InputType::class, [])
            ->add('admin', UserType::class, [])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'forms.buttons.submit',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'forms.buttons.cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Authority::class,
        ]);
    }
}
