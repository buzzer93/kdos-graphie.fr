<?php

namespace App\Form;

use App\Entity\Order;
use App\Form\OrderItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $statusChoices = array_flip(Order::getStatusLabels());

        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 50),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $statusChoices,
                'constraints' => [
                    new Choice(choices: array_keys(Order::getStatusLabels())),
                ],
            ])
            ->add('customerName', TextType::class, [
                'label' => 'Nom client',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Email client',
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                    new Length(max: 255),
                ],
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
        ]);
    }
}
