<?php

namespace LFR\StoreBundle\Controller;

use LFR\StoreBundle\Entity\Text;
use LFR\StoreBundle\Entity\Image;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

$textRepository = null;
class InformationController extends Controller
{
    public function fetchText($role){
      $textRepository = $this->getDoctrine()->getManager()->getRepository('LFRStoreBundle:Text');
      return $textRepository->findBy(array('role' => $role))[0];
    }
    public function fetchImage($role){
      $textRepository = $this->getDoctrine()->getManager()->getRepository('LFRStoreBundle:Image');
      $image = $textRepository->findBy(array('role' => $role))[0];
      $filename = $image->getImage();
      $imagehandler = $this->container->get('lfr_store.imagehandler');
      $path_small_image = $imagehandler->get_image_in_quality($filename, 'md');
      $image->small_image = $path_small_image;
      return $image;
    }

    public $entityNameSpace = 'LFRStoreBundle:Information';
    public function historyAction()
    {
      $text = [];
      $text['history']['title'] = $this->fetchText('history:title');
      $text['history']['content'] = $this->fetchText('history:text');
      $text['history']['image_message'] = $this->fetchImage('history:image_message');
      $text['history']['under'] = $this->fetchImage('history:under');
      $text['history']['over'] = $this->fetchImage('history:over');
      $text['author']['title'] = $this->fetchText('author:title');
      $text['author']['content'] = $this->fetchText('author:text');
      $text['author']['img'] = $this->fetchImage('author:img');
      return $this->render($this->entityNameSpace.':history.html.twig', array(
        'data' => $text
      ));
    }
    public function textAddAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $text = new Text();
        } else {
            $repository = $em->getRepository('LFRStoreBundle:Text');
            $text = $repository->find($id);
            $text_role = $text->getRole();
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $text)
        ->add('role', TextType::class)
        ->add('text', TextareaType::class)
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isValid()) {
            $em->persist($text);
            $em->flush();
            return $this->redirect($this->generateUrl('lfr_store_homepage'));
        } elseif ($request->getMethod() == 'POST') {
            $data = $request->get('form');
            $text = $repository->find($id);
            $text->setText($data['text']);
            $text->setRole($text_role);
            $em->persist($text);
            $em->flush();
            return $this->redirect($request->headers->get('referer'));
        }
        return $this->render('LFRStoreBundle:Text:add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id
        ));
    }

    public function imageAddAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('LFRStoreBundle:Image');
        $oldFileName = null;
        if($id == 0) {
            $image = new Image();
        } else {
            $image = $repository->find($id);
            $image_role = $image->getRole();
            if($image->getImage() != ''){
              $oldFileName = $image->getImage();
              $image->setImage(
                  new File($this->getParameter('img_dir').'/'.$image->getImage())
              );
            }
        }
        if($oldFileName != null) {
          $article_img_url = $oldFileName;
        } else {
          $article_img_url = '';
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $image)
        ->add('role', TextType::class)
        ->add('image', FileType::class, array('label' => 'Image', 'required' => False))
        ->add('credit', TextType::class)
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $file = $image->getImage();
            if($file != null) {
              $fileName = md5(uniqid()).'.'.$file->guessExtension();
              $file->move(
                  $this->getParameter('img_dir'),
                  $fileName
              );
              // Check orientation
              $path = $this->getParameter('img_dir').'/'.$fileName;
              $imagehandler = $this->container->get('lfr_store.imagehandler');
              $imagehandler->image_fix_orientation($path);
              // Update the 'image' property to store the file name instead of its contents
              $image->setImage($fileName);
            } elseif($oldFileName != null) {
              $image->setImage($oldFileName);
            } else {
              $image->setImage('');
            }
            $em->persist($image);
            $em->flush();
            return $this->redirect($this->generateUrl('lfr_store_homepage'));
        } elseif ($request->getMethod() == 'POST') {
            $data = $request->get('form');
            $image = $repository->find($id);
            $image->setRole($image_role);
            $file = $image->getImage();
            if($file != null) {
              $fileName = md5(uniqid()).'.'.$file->guessExtension();
              $file->move(
                  $this->getParameter('img_dir'),
                  $fileName
              );
              // Check orientation
              $path = $this->getParameter('img_dir').'/'.$fileName;
              $imagehandler = $this->container->get('lfr_store.imagehandler');
              $imagehandler->image_fix_orientation($path);
              // Update the 'image' property to store the file name instead of its contents
              $image->setImage($fileName);
            } elseif($oldFileName != null) {
              $image->setImage($oldFileName);
            } else {
              $image->setImage('');
            }
            $em->persist($image);
            $em->flush();
            return $this->redirect($request->headers->get('referer'));
        }
        return $this->render('LFRStoreBundle:Image:add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id,
            'img' => $article_img_url,
        ));
    }

    public function contactAction()
    {
        $text = [];
        $text['mail']['title'] = $this->fetchText('mail:title');
        $text['mail']['content'] = $this->fetchText('mail:content');
        $text['follow']['title'] = $this->fetchText('follow:title');
        $text['follow']['content'] = $this->fetchText('follow:content');
        return $this->render($this->entityNameSpace.':contact.html.twig', array(
          'data' => $text
        ));
    }
    static public function slugify($text){
      //on sauve les accents classiques
      $text = preg_replace('#Ç#', 'C', $text);
      $text = preg_replace('#ç#', 'c', $text);
      $text = preg_replace('#è|é|ê|ë#', 'e', $text);
      $text = preg_replace('#È|É|Ê|Ë#', 'E', $text);
      $text = preg_replace('#à|á|â|ã|ä|å#', 'a', $text);
      $text = preg_replace('#@|À|Á|Â|Ã|Ä|Å#', 'A', $text);
      $text = preg_replace('#ì|í|î|ï#', 'i', $text);
      $text = preg_replace('#Ì|Í|Î|Ï#', 'I', $text);
      $text = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $text);
      $text = preg_replace('#Ò|Ó|Ô|Õ|Ö#', 'O', $text);
      $text = preg_replace('#ù|ú|û|ü#', 'u', $text);
      $text = preg_replace('#Ù|Ú|Û|Ü#', 'U', $text);
      $text = preg_replace('#ý|ÿ#', 'y', $text);
      $text = preg_replace('#Ý#', 'Y', $text);
      // replace non letter or digits by -
      $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
      // trim
      $text = trim($text, '-');
        // transliterate
      $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
      // lowercase
      $text = strtolower($text);
      // remove unwanted characters
      $text = preg_replace('~[^-\w]+~', '', $text);
      if (empty($text))
        return 'n-a';
      return $text;
    }

    public function collectionAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('LFRStoreBundle:Collection');
        $collections = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('lfr_store.imagehandler');
        foreach ($collections as $collection) {
          // tmp fix for old collection
          $slug = $this->slugify($collection->getTitle());
          $collection->setSlug($slug);
          $em->persist($collection);
          $filename = $collection->getImage();
          $path_small_image = $imagehandler->get_image_in_quality($filename, 'sm');
          $collection->small_image = $path_small_image;
        }
        $em->flush();
        return $this->render($this->entityNameSpace.':collection.html.twig', array(
          'collections' => $collections,
        ));
    }
}
