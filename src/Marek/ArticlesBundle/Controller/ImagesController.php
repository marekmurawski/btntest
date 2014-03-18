<?php

namespace Marek\ArticlesBundle\Controller;


use Marek\ArticlesBundle\Entity\Article;
use Marek\ArticlesBundle\Entity\Image;
use Marek\ArticlesBundle\Form\ArticleType;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/articles/image")
 */
class ImagesController extends Controller
{

    /**
     * @Route("/delete", name="marek_image_delete")
     * Template()
     */
    public function deleteAction(Request $request)
    {
        $id = $request->request->get('id');
        $image = $this->getDoctrine()
            ->getRepository('MarekArticlesBundle:Image')
            ->find($id);

        if ($image) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();
            return new JsonResponse(array(
                'status'=>'OK',
            ),200);
        } else {
            return new JsonResponse(array(
                'status'=>'Image not found!',
            ),404);
        }

    }

}
