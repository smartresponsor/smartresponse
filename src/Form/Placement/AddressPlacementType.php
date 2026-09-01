<?php

declare(strict_types=1);

namespace App\Form\Placement;

use App\Dto\Placement\AddressPlacementFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AddressPlacementType extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('line1', TextType::class, ['label' => 'Address'])
            ->add('line2', TextType::class, ['label' => 'Address line 2', 'required' => false])
            ->add('city', TextType::class, ['label' => 'City'])
            ->add('region', TextType::class, ['label' => 'State / region', 'required' => false])
            ->add('postalCode', TextType::class, ['label' => 'Postal code', 'required' => false])
            ->add('countryCode', CountryType::class, ['label' => 'Country']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddressPlacementFormData::class,
            'csrf_protection' => true,
        ]);
    }
}
