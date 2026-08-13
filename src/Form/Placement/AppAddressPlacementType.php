<?php

declare(strict_types=1);

namespace App\Form\Placement;

use App\Dto\Placement\AppAddressPlacementFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AppAddressPlacementType extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('originLine1', TextType::class, ['label' => 'Origin address'])
            ->add('originLine2', TextType::class, ['label' => 'Origin address line 2', 'required' => false])
            ->add('originCity', TextType::class, ['label' => 'Origin city'])
            ->add('originRegion', TextType::class, ['label' => 'Origin state / region', 'required' => false])
            ->add('originPostalCode', TextType::class, ['label' => 'Origin postal code', 'required' => false])
            ->add('originCountryCode', CountryType::class, ['label' => 'Origin country'])
            ->add('destinationLine1', TextType::class, ['label' => 'Destination address'])
            ->add('destinationLine2', TextType::class, ['label' => 'Destination address line 2', 'required' => false])
            ->add('destinationCity', TextType::class, ['label' => 'Destination city'])
            ->add('destinationRegion', TextType::class, ['label' => 'Destination state / region', 'required' => false])
            ->add('destinationPostalCode', TextType::class, ['label' => 'Destination postal code', 'required' => false])
            ->add('destinationCountryCode', CountryType::class, ['label' => 'Destination country']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppAddressPlacementFormData::class,
            'csrf_protection' => true,
        ]);
    }
}
