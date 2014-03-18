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
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/articles")
 */
class ArticlesController extends Controller
{
    private $errors = null;
    /**
     * @Route("/", name="marek_articles_index")
     */
    public function indexAction(Request $request)
    {
        // Get total count of Articles
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('count(Article.id)');
        $qb->from('MarekArticlesBundle:Article','Article');

        // Pagination setup
        $pagination['limit'] = 5;
        $pagination['currentPage'] = (int) $request->query->get('page') ?: 1;
        $pagination['offset'] = ($pagination['currentPage'] - 1) * $pagination['limit'];
        $pagination['total'] = $qb->getQuery()->getSingleScalarResult();
        $pagination['lastPage'] = ceil($pagination['total']/$pagination['limit']);

        // Retrieve Articles
        $articles = $this->getDoctrine()
            ->getRepository('MarekArticlesBundle:Article')
            ->findBy(array(),
                array('position' => 'DESC', 'createdOn' => 'DESC'),
                $pagination['limit'], //limit
                $pagination['offset']  //offset
            );
        return $this->render('MarekArticlesBundle:Articles:index.html.twig', compact('articles','pagination'));
    }

    /**
     * Just show the empty form
     *
     * @Route("/add", name="marek_article_add")
     * @Method({"GET"})
     */
    public function addAction(Request $request)
    {
        $frm = $this->createForm(new ArticleType());
        $frm->handleRequest($request);

        return $this->render('MarekArticlesBundle:Articles:manage.html.twig', array(
            'form' => $frm->createView(),
            'editing' => false
        ));
    }

    /**
     * Handle new article storage
     *
     * @Route("/add", name="marek_article_store")
     * @Method({"POST"})
     */
    public function storeAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new ArticleType());
        $form->handleRequest($request);

        if ($form->isValid()) {

            $article = new Article();
            $data = $form->getData();

            // assign user-provided data
            $article->setName($data->getName());
            $article->setDescription($data->getDescription());
            $article->setActive($data->getActive() ?: false);

            // calculate position for new Article
            $maxPosQuery = $em->createQuery('SELECT MAX(a.position) FROM Marek\ArticlesBundle\Entity\Article a');
            $newPosition = $maxPosQuery->getSingleScalarResult() + 1;
            $article->setPosition($newPosition);

            // try to save all attached files
            // if any file validation fails show form again
            if (!$this->_storeAttachedImages($request,$article,$em)) {
                return $this->render('MarekArticlesBundle:Articles:manage.html.twig', array(
                    'form' => $form->createView(),
                    'file_errors' => $this->errors,
                    'editing' => false,
                ));
            };

            // Persist new Article object and store to DB
            $em->persist($article);
            $em->flush();

            // Flash message
            $this->get('session')->getFlashBag()->add(
                'messages',
                'Artykuł został dodany!'
            );

            // Decide where to go based on clicked button
            if ($form->get('save_and_continue')->isClicked()) {
                return $this->redirect($this->generateUrl('marek_article_edit',array(
                    'id' => $article->getId()
                )));
            } else {
                return $this->redirect($this->generateUrl('marek_articles_index'));
            }

        }

        return $this->render('MarekArticlesBundle:Articles:manage.html.twig', array(
            'form' => $form->createView(),
            'editing' => false
        ));
    }

    /**
     * Just show the edit form
     *
     * @Route("/edit/{id}", name="marek_article_edit")
     * @Method({"GET"})
     */
    public function editAction(Request $request, $id)
    {
        $article = $this->getDoctrine()
            ->getRepository('MarekArticlesBundle:Article')
            ->find($id);

        $form = $this->createForm(new ArticleType(),$article);

        $images = $article->getImages();

        return $this->render('MarekArticlesBundle:Articles:manage.html.twig', array(
            'form' => $form->createView(),
            'article' => $article,
            'images' => $images,
            'editing' => true
        ));
    }

    /**
     * @Route("/edit/{id}", name="marek_article_update")
     * @Method({"POST"})
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $article = $this->getDoctrine()
            ->getRepository('MarekArticlesBundle:Article')
            ->find($id);

        if ($article) {
            $images = $article->getImages();

            // attach all valid images
            $this->_storeAttachedImages($request, $article, $em);
            $em->flush();

            $form = $this->createForm(new ArticleType());
            $form->handleRequest($request);

            // if form AND files are valid
            if ($form->isValid() && empty($this->errors)) {

                $data = $form->getData();
                // assign user-provided data
                $article->setName($data->getName());
                $article->setDescription($data->getDescription());
                $article->setActive($data->getActive() ?: false);

                $em->persist($article);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'messages',
                    'Zmiany w artykule zapisane!'
                );

                if ($form->get('save_and_continue')->isClicked()) {
                    return $this->redirect($this->generateUrl('marek_article_edit',array(
                        'id' => $article->getId()
                    )));
                } else {
                    return $this->redirect($this->generateUrl('marek_articles_index'));
                }

            }

            return $this->render('MarekArticlesBundle:Articles:manage.html.twig', array(
                'form' => $form->createView(),
                'article' => $article,
                'images' => $images,
                'file_errors' => $this->errors,
                'editing' => true
            ));
        }
        else {
            die('Artykuł nie istnieje!');
        }
    }

    /**
     * Display the preview page
     *
     * @Route("/preview/{id}", name="marek_article_preview")
     */
    public function previewAction($id)
    {
        $article = $this->getDoctrine()
            ->getRepository('MarekArticlesBundle:Article')
            ->find($id);

        $images = $article->getImages();

        return $this->render('MarekArticlesBundle:Articles:preview.html.twig', compact('article','images'));
    }

    /**
     * Remove article
     *
     * @Route("/delete/{id}", name="marek_article_delete")
     */
    public function deleteAction(Request $request,$id)
    {
        $article = $this->getDoctrine()
            ->getRepository('MarekArticlesBundle:Article')
            ->find($id);

        if ($article) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($article);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'messages',
                'Artykuł "' . $article->getName() . '" został usunięty!'
            );

        } else {
            $this->get('session')->getFlashBag()->add(
                'errors',
                "Artykuł o id=$id nie został znaleziony!"
            );
        }
        return $this->redirect($this->generateUrl('marek_articles_index',
            $request->query->all() // preserve pagination-page
        ));

    }

    /**
     * Move article by swapping it's position with neighbour
     *
     * @Route("/move/{id}/{direction}", name="marek_article_move")
     */
    public function moveAction(Request $request, $id, $direction)
    {
        $em = $this->getDoctrine()->getManager();

        // Get the base article
        $article = $this->getDoctrine()
            ->getRepository('MarekArticlesBundle:Article')
            ->find($id);

        // Prepare options
        if ($direction==='down') {
            $dirOperator = '<';
            $dirOrdering = 'DESC';
        } elseif ($direction==='up') {
            $dirOperator = '>';
            $dirOrdering = 'ASC';
        } else {
            die('Invalid direction');
        }


        $art1pos = $article->getPosition();

        //print_r($article->getPosition());

        $query = $em->createQuery('SELECT a FROM MarekArticlesBundle:Article a WHERE a.position '
            . $dirOperator // $dirOperator is safe
            .' :ref ORDER BY a.position ' . $dirOrdering) // $dirOrdering is safe
            ->setParameter('ref', $art1pos)
            ->setMaxResults(1);

        // Get the neighbour article
        // and if it's found, swap the articles' positions
        if ($swapArticle = $query->getOneOrNullResult()) {
            $art2pos = $swapArticle->getPosition();
            $swapArticle->setPosition($art1pos);
            $article->setPosition($art2pos);
            $em->persist($article);
            $em->persist($swapArticle);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('marek_articles_index',
            $request->query->all() // preserve pagination-page
        ));

    }

    /**
     * AJAX: Toggle article's active state
     *
     * @Route("/switchActive", name="marek_article_switch_active")
     */
    public function switchActiveAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getManager();
            $id = $request->request->get('id');
            $article = $this->getDoctrine()
                ->getRepository('MarekArticlesBundle:Article')
                ->find($id);

            if ($article) {
                $article->setActive(!$article->getActive());
                $em->persist($article);
                $em->flush();

                $data = array(
                    'status' => 'OK',
                    'active' => $article->getActive()
                );
                return new JsonResponse($data,200);
            }

            return new JsonResponse(array(
                'status' => 'error'
            ),404);
        }
        return new Response('Non-ajax request!');
    }

    /**
     * Helper for saving images attached to form
     *
     * @param Request $request
     * @param Article $article
     * @param $em
     * @return bool
     */
    private function _storeAttachedImages(Request $request, Article $article, $em)
    {
        // $this->errors will hold any validation error messages in uploaded files
        $this->errors = null;

        // iterate the uloaded files
        foreach($request->files->get('article[images]',array(),true) as $uploadedFile) {

            if ($uploadedFile instanceof UploadedFile) {
                $image = new Image();
                $image->setFile($uploadedFile);
                $image->setArticle($article);

                $validator = $this->get('validator');
                $file_errors = $validator->validate($image);

                // store messages if validation failed
                if (count($file_errors)) {
                    $messages = array();
                    foreach ($file_errors as $message) {
                        $messages[] = $message->getMessage();
                    };

                    $this->errors[] = array(
                        'file' => $uploadedFile->getClientOriginalName(),
                        'messages' => $messages
                    );
                } else {
                    // persist image if all is OK
                    $em->persist($image);
                }

            }
        };
        // return true if validation errors
        if ($this->errors) {
            return false;
        } else {
            return true;
        }
    }

}