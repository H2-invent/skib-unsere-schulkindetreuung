<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */
namespace App\Form\Type;

use App\Entity\Stadt;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class,array('label'=>'Username','required'=>true,'translation_domain' => 'form'))
            ->add('email', EmailType::class,array('label'=>'Email','required'=>true,'translation_domain' => 'form'))
            ->add('plainPassword', TextType::class,array('required'=>true,'label'=>'Password','translation_domain' => 'form'))
            ->add('vorname', EmailType::class,array('label'=>'Vorname','required'=>true,'translation_domain' => 'form'))
            ->add('nachname', EmailType::class,array('label'=>'Name','required'=>true,'translation_domain' => 'form'))
            ->add('birthday', BirthdayType::class,array('required'=>false,'label'=>'Geburtstag','translation_domain' => 'form'))
            ->add('save', SubmitType::class, ['label' => 'Speichern','translation_domain' => 'form'])
        ;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
