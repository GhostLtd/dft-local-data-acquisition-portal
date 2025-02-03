<?php

namespace App\Form\Type;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    public function __construct(protected string $appEnvironment)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', InputType::class, [
                'label' => 'auth.login.email.label',
                'attr' => ['class' => 'govuk-input--width-10'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'auth.login.email_blank',
                    ]),
                    new Email([
                        'message' => 'auth.login.invalid_email',
                    ]),
                ],
            ])
            ->add('sign_in', ButtonType::class, [
                'label' => 'auth.login.sign_in.label',
                'type' => 'submit'
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        if ($this->appEnvironment !== 'dev') {
            $resolver->setDefault('attr', ['autocomplete' => 'off']);
        }
    }
}
