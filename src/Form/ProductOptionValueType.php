<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ProductOptionValue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductOptionValueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Valeur',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 100),
                ],
            ])
            ->add('priceAdjustment', NumberType::class, [
                'label' => 'Supplément de prix (€)',
                'scale' => 2,
                'html5' => true,
                'required' => false,
                'empty_data' => '0',
                'attr' => ['step' => '0.01', 'min' => '0'],
                'constraints' => [
                    new GreaterThanOrEqual(0),
                ],
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ]);

        $builder->get('priceAdjustment')->addModelTransformer(new CallbackTransformer(
            fn(mixed $centimes) => is_int($centimes) ? $centimes / 100 : 0.0,
            fn(mixed $euros) => (int) round((float) $euros * 100),
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductOptionValue::class,
        ]);
    }
}
