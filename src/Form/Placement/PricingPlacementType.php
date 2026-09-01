<?php

declare(strict_types=1);

namespace App\Form\Placement;

use App\Dto\Placement\PricingPlacementFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PricingPlacementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('model', ChoiceType::class, ['choices' => [
                'Fixed price' => 'fixed', 'Hourly' => 'hourly', 'Minimum project' => 'minimum',
                'Quote required' => 'quote', 'Budget' => 'budget', 'Budget range' => 'range', 'Deposit' => 'deposit',
            ]])
            ->add('amountMinor', IntegerType::class, ['required' => false, 'label' => 'Primary amount (minor units)'])
            ->add('maximumAmountMinor', IntegerType::class, ['required' => false, 'label' => 'Maximum amount (minor units)'])
            ->add('currency', TextType::class, ['label' => 'Currency']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PricingPlacementFormData::class, 'csrf_protection' => true]);
    }
}
