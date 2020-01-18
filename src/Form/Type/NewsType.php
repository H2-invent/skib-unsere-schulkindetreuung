<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */
namespace App\Form\Type;

use App\Entity\Active;

use App\Entity\News;
use App\Entity\Organisation;
use App\Entity\Schule;
use Doctrine\DBAL\Types\BooleanType;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('title', TextType::class,array('label'=>'Titel','required'=>true,'translation_domain' => 'form'))
            ->add('message', TextareaType::class,array('label'=>'Nachricht','required'=>true,'translation_domain' => 'form'))
            ->add('schule', EntityType::class, [
                'choice_label' => 'name',
                'class' => Schule::class,
                'choices' => $options['schulen'],
                'label'=>'Name der Schule für den Versand der Nachricht',
                'translation_domain' => 'form',
                'multiple' =>true,
            ])
            ->add('activ', CheckboxType::class,array('label'=>'Neuigkeit auf der Startseite sichtbar','translation_domain' => 'form'))

            ->add('save', SubmitType::class, ['label' => 'Speichern','translation_domain' => 'form'])
        ;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => News::class,
            'schulen' => array(),
        ]);
    }
}
