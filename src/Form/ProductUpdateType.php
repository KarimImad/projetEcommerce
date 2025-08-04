<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\SubCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ProductUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('Description')
            ->add('Price')
            ->add('image', FileType::class,[
               'label'=> 'Image du produit',
               'mapped'=> false,
               'required'=>false,
               'constraints'=>[
                new File([
                    'maxSize'=>'1024k',
                    'mimeTypes'=>[
                        'image/jpeg',
                        'image/png',
                        'image/jpg',
                        'image/webp'

                    ],
                    'mimeTypesMessage' => 'Veuillez choisir un fichier de type image valide (jpeg,png, jpg)',
                    
                ])
               ] 
            ])
            //  ->add('stock')
            ->add('SubCategory', EntityType::class, [
                'class' => SubCategory::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
