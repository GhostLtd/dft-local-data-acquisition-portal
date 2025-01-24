<?php

namespace App\Form\SchemeReturn\Crsts;

use App\Entity\Enum\BenefitCostRatioType as BenefitCostRatioTypeEnum;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\OnTrackRating;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MilestoneRatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('onTrackRating', ChoiceType::class, [
                'label' => "forms.scheme.milestone_progress.on_track_rating.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_progress.on_track_rating.help",
                'choices' => OnTrackRating::cases(),
                'choice_label' => fn(OnTrackRating $choice) => "enum.on_track_rating.{$choice->value}",
                'choice_value' => fn(?OnTrackRating $choice) => $choice?->value,
                'expanded' => false,
            ])
            ->add('progressUpdate', TextareaType::class, [
                'label' => "forms.scheme.milestone_progress.progress_update.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "forms.scheme.milestone_progress.progress_update.help",
            ]);
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', CrstsSchemeReturn::class);
    }
}
