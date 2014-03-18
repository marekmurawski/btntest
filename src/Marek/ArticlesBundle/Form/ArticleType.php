<?php
namespace Marek\ArticlesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            /*
             *
             ->add('id','text',array(
                'mapped' => false
            ))
            */
            ->add('description','textarea',array('required'=>false))
            ->add('images', 'file', array(
                'mapped' => false,
                'attr' => array(
                    'multiple' => 'multiple',
                )
            ))
            ->add('active','checkbox', array(
                'required'  => false,
            ))
            ->add('save', 'submit')
            ->add('save_and_continue', 'submit')
            ->add('reset', 'reset')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Marek\ArticlesBundle\Entity\Article',
        ));
    }

    public function getName()
    {
        return 'article';
    }
}