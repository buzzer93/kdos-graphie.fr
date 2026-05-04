<?php

namespace App\Form;

use App\Entity\Order;
use App\Form\OrderItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $lockCommercialData = (bool) $options['lock_commercial_data'];

        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'disabled' => $lockCommercialData,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 50),
                ],
            ])
            ->add('customerFirstName', TextType::class, [
                'label' => 'Prenom client',
                'disabled' => $lockCommercialData,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('customerLastName', TextType::class, [
                'label' => 'Nom client',
                'disabled' => $lockCommercialData,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Email client',
                'disabled' => $lockCommercialData,
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                    new Length(max: 255),
                ],
            ])
            ->add('customerPhone', TextType::class, [
                'label' => 'Telephone',
                'disabled' => $lockCommercialData,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 50),
                ],
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Adresse de livraison',
                'disabled' => $lockCommercialData,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('additionalInfo', TextareaType::class, [
                'label' => 'Informations complementaires',
                'disabled' => $lockCommercialData,
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'disabled' => $lockCommercialData,
                'label' => 'Lignes de commande',
            ])
            ->add('total', IntegerType::class, [
                'label' => 'Total (centimes)',
                'disabled' => true,
                'constraints' => [
                    new GreaterThanOrEqual(0),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'lock_commercial_data' => false,
        ]);

        $resolver->setAllowedTypes('lock_commercial_data', 'bool');
    }
}
