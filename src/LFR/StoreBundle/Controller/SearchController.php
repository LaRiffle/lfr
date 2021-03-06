<?php

namespace LFR\StoreBundle\Controller;

use LFR\StoreBundle\Entity\Creation;
use LFR\StoreBundle\Entity\Gender;
use LFR\StoreBundle\Entity\Type;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class SearchController extends Controller
{
    public $entityNameSpace = 'LFRStoreBundle:Search';
    public function collectionAction($collection = '_', $category = '_')
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('LFRStoreBundle:Creation');
        $creations = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('lfr_store.imagehandler');
        foreach ($creations as $creation) {
          $fileNames = $creation->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $creation->small_image = $path_small_image;
        }
        $repository = $em->getRepository('LFRStoreBundle:Gender');
        $genders = $repository->findAll();
        $typeRepository = $em->getRepository('LFRStoreBundle:Type');
        foreach ($genders as $gender) {
          $gender->types = $typeRepository->whereGender($gender->getId());
        }
        $repository = $em->getRepository('LFRStoreBundle:Collection');
        if($collection != '_'){
          $collection_id = $repository->findOneBy(array('slug' => $collection))->getId();
        } else {
          $collection_id = $collection;
        }

        $collections = $repository->findAll();
        $repository = $em->getRepository('LFRStoreBundle:Category');
        $categories = $repository->findAll();
        return $this->render($this->entityNameSpace.':collection.html.twig', array(
          'creations' => $creations,
          'genders' => $genders,
          'collections' => $collections,
          'collection_id' => $collection_id,
          'categories' => $categories,
          'category_id' => $category
        ));
    }
    public function selectionAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('LFRStoreBundle:Creation');
        $creations = $repository->findBy(array(), array('id' => 'desc'));
        $user = $this->getUser();
        $loved_creations = [];
        foreach ($creations as $creation) {
          foreach ($creation->getLovers() as $lover) {
            if($lover->getUsername() == $user->getUsername()){
              $loved_creations[] = $creation;
            }
          }
        }
        $imagehandler = $this->container->get('lfr_store.imagehandler');
        foreach ($loved_creations as $creation) {
          $fileNames = $creation->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $creation->small_image = $path_small_image;
        }
        $repository = $em->getRepository('LFRStoreBundle:Gender');
        $genders = $repository->findAll();
        $typeRepository = $em->getRepository('LFRStoreBundle:Type');
        foreach ($genders as $gender) {
          $gender->types = $typeRepository->whereGender($gender->getId());
        }
        $repository = $em->getRepository('LFRStoreBundle:Collection');
        $collections = $repository->findAll();
        $repository = $em->getRepository('LFRStoreBundle:Category');
        $categories = $repository->findAll();
        return $this->render($this->entityNameSpace.':selection.html.twig', array(
            'creations' => $loved_creations,
            'genders' => $genders,
            'collections' => $collections,
            'collection_id' => '_',
            'categories' => $categories,
            'category_id' => '_'
        ));
    }
    public function showAction()
    {
        return $this->render($this->entityNameSpace.':show.html.twig');
    }
    public function genderAddAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $gender = new Gender();
        } else {
            $repository = $em->getRepository('LFRStoreBundle:Gender');
            $gender = $repository->find($id);
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $gender)
        ->add('name', TextType::class)
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isValid()) {
            $gender->setSlug($gender->getName());
            $em->persist($gender);
            $em->flush();
            return $this->redirect($this->generateUrl('lfr_store_explore'));
        }
        return $this->render('LFRStoreBundle:Filter:gender_add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id
        ));
    }
    public function genderRemoveAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $gender= $em->getRepository('LFRStoreBundle:Gender')->find($id);

        $typeRepository = $em->getRepository('LFRStoreBundle:Type');
        $types = $typeRepository->whereGender($gender->getId());
        foreach ($types as $type) {
            $em->remove($type);
        }
        $em->remove($gender);
        $em->flush();
        return $this->redirect($this->generateUrl('lfr_store_explore'));
    }
    public function typeAddAction(Request $request, $gender_id, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $type = new Type();
        } else {
            $repository = $em->getRepository('LFRStoreBundle:Type');
            $type = $repository->find($id);
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $type)
        ->add('name', TextType::class)
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isValid()) {
            $type->setSlug($type->getName());
            $genderRepository = $em->getRepository('LFRStoreBundle:Gender');
            $gender = $genderRepository->find($gender_id);
            $type->setGender($gender);

            $em->persist($type);
            $em->flush();
            return $this->redirect($this->generateUrl('lfr_store_explore'));
        }
        return $this->render('LFRStoreBundle:Filter:type_add.html.twig', array(
            'form' => $form->createView(),
            'gender_id' => $gender_id,
            'id' => $id
        ));

    }
    public function typeRemoveAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $type = $em->getRepository('LFRStoreBundle:Type')->find($id);
        $em->remove($type);
        $em->flush();
        return $this->redirect($this->generateUrl('lfr_store_explore'));
    }
}
