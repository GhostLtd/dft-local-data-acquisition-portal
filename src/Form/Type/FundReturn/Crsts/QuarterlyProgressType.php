<?php

namespace App\Form\Type\FundReturn\Crsts;

use App\Entity\Enum\Rating;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Form\Type\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuarterlyProgressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ragProgressSummary', TextareaType::class, [
                'label' => 'forms.crsts.quarterly_progress.progress_summary.label',
                'label_attr' => ['class' => 'govuk-label--s']
            ])
            ->add('ragProgressRating', ChoiceType::class, [
                'label' => 'forms.crsts.quarterly_progress.progress_rating.label',
                'choices' => Rating::cases(),
                'choice_label' => fn(Rating $choice) => "enum.rating.{$choice->value}",
                'choice_value' => fn(?Rating $choice) => $choice?->value,
                'label_attr' => ['class' => 'govuk-fieldset__legend--s']
            ]);
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CrstsFundReturn::class,
            'validation_groups' => ['quarterly_progress'],
        ]);
    }
}
