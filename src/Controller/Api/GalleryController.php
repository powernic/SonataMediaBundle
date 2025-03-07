<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Controller\Api;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View as FOSRestView;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Sonata\DatagridBundle\Pager\PagerInterface;
use Sonata\MediaBundle\Form\Type\ApiGalleryHasMediaType;
use Sonata\MediaBundle\Form\Type\ApiGalleryType;
use Sonata\MediaBundle\Model\GalleryHasMediaInterface;
use Sonata\MediaBundle\Model\GalleryInterface;
use Sonata\MediaBundle\Model\GalleryManagerInterface;
use Sonata\MediaBundle\Model\GalleryMediaCollectionInterface;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @final since sonata-project/media-bundle 3.21.0
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class GalleryController
{
    /**
     * @var GalleryManagerInterface
     */
    protected $galleryManager;

    /**
     * @var MediaManagerInterface
     */
    protected $mediaManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var string
     */
    protected $galleryHasMediaClass;

    /**
     * @param string $galleryHasMediaClass
     */
    public function __construct(GalleryManagerInterface $galleryManager, MediaManagerInterface $mediaManager, FormFactoryInterface $formFactory, $galleryHasMediaClass)
    {
        $this->galleryManager = $galleryManager;
        $this->mediaManager = $mediaManager;
        $this->formFactory = $formFactory;
        $this->galleryHasMediaClass = $galleryHasMediaClass;
    }

    /**
     * Retrieves the list of galleries (paginated).
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Retrieves the list of galleries (paginated).",
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page for gallery list pagination",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="count",
     *         in="query",
     *         description="Number of galleries by page",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="enabled",
     *         in="query",
     *         description="Enables or disables galleries filter",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="Order by array (key is field, value is direction)",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type="Sonata\DatagridBundle\Pager\PagerInterface"))
     *     )
     * )
     *
     * @Rest\QueryParam(name="page", requirements="\d+", default="1", description="Page for gallery list pagination")
     * @Rest\QueryParam(name="count", requirements="\d+", default="10", description="Number of galleries per page")
     * @Rest\QueryParam(name="enabled", requirements="0|1", nullable=true, strict=true, description="Enables or disables the galleries filter")
     * @Rest\QueryParam(name="orderBy", map=true, requirements="ASC|DESC", nullable=true, strict=true, description="Order by array (key is field, value is direction)")
     *
     * @Rest\View(serializerGroups={"sonata_api_read"}, serializerEnableMaxDepthChecks=true)
     *
     * @return PagerInterface
     */
    public function getGalleriesAction(ParamFetcherInterface $paramFetcher)
    {
        $supportedCriteria = [
            'enabled' => '',
        ];

        $page = $paramFetcher->get('page');
        $limit = $paramFetcher->get('count');
        $sort = $paramFetcher->get('orderBy');
        $criteria = array_intersect_key($paramFetcher->all(), $supportedCriteria);

        foreach ($criteria as $key => $value) {
            if (null === $value) {
                unset($criteria[$key]);
            }
        }

        if (null === $sort) {
            $sort = [];
        } elseif (!\is_array($sort)) {
            $sort = [$sort => 'asc'];
        }

        return $this->getGalleryManager()->getPager($criteria, $page, $limit, $sort);
    }

    /**
     * Retrieves a specific gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Retrieves a specific gallery.",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type="sonata_media_api_form_gallery"))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when gallery is not found"
     *     )
     * )
     *
     * @Rest\View(serializerGroups={"sonata_api_read"}, serializerEnableMaxDepthChecks=true)
     *
     * @param string $id Gallery identifier
     *
     * @return GalleryInterface
     */
    public function getGalleryAction($id)
    {
        return $this->getGallery($id);
    }

    /**
     * Retrieves the medias of specified gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Retrieves the medias of specified gallery.",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type="Sonata\MediaBundle\Model\Media"))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when gallery is not found"
     *     )
     * )
     *
     * @Rest\View(serializerGroups={"sonata_api_read"}, serializerEnableMaxDepthChecks=true)
     *
     * @param string $id Gallery identifier
     *
     * @return MediaInterface[]
     */
    public function getGalleryMediasAction($id)
    {
        $ghms = $this->getGallery($id)->getGalleryHasMedias();

        $media = [];
        foreach ($ghms as $ghm) {
            $media[] = $ghm->getMedia();
        }

        return $media;
    }

    /**
     * Retrieves the galleryhasmedias of specified gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Retrieves the list of galleries (paginated).",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type="Sonata\MediaBundle\Model\GalleryHasMedia"))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when gallery is not found"
     *     )
     * )
     *
     * @Rest\View(serializerGroups={"sonata_api_read"}, serializerEnableMaxDepthChecks=true)
     *
     * @param string $id Gallery identifier
     *
     * @return GalleryHasMediaInterface[]
     */
    public function getGalleryGalleryhasmediasAction($id)
    {
        return $this->getGallery($id)->getGalleryHasMedias();
    }

    /**
     * Adds a gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Adds a gallery.",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type="sonata_media_api_form_gallery"))
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when an error has occurred while gallery creation"
     *     )
     * )
     *
     * @param Request $request Symfony request
     *
     * @throws NotFoundHttpException
     *
     * @return GalleryInterface
     */
    public function postGalleryAction(Request $request)
    {
        return $this->handleWriteGallery($request);
    }

    /**
     * Updates a gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Updates a gallery.",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type="sonata_media_api_form_gallery"))
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when an error has occurred while gallery creation"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when unable to find gallery"
     *     )
     * )
     *
     * @param string  $id      Gallery identifier
     * @param Request $request Symfony request
     *
     * @throws NotFoundHttpException
     *
     * @return GalleryInterface
     */
    public function putGalleryAction($id, Request $request)
    {
        return $this->handleWriteGallery($request, $id);
    }

    /**
     * Adds a medium to a gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Retrieves a specific gallery.",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type="sonata_media_api_form_gallery"))
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when an error has occurred while gallery/media attachment"
     *     )
     * )
     *
     * @param string  $galleryId Gallery identifier
     * @param string  $mediaId   Medium identifier
     * @param Request $request   Symfony request
     *
     * @throws NotFoundHttpException
     *
     * @return GalleryInterface
     */
    public function postGalleryMediaGalleryhasmediaAction($galleryId, $mediaId, Request $request)
    {
        $gallery = $this->getGallery($galleryId);
        $media = $this->getMedia($mediaId);
        $galleryHasMediaExists = $gallery->getGalleryHasMedias()->exists(static function ($key, GalleryHasMediaInterface $element) use ($media): bool {
            return $element->getMedia()->getId() === $media->getId();
        });

        if ($galleryHasMediaExists) {
            return FOSRestView::create([
                'error' => sprintf('Gallery "%s" already has media "%s"', $galleryId, $mediaId),
            ], 400);
        }

        return $this->handleWriteGalleryhasmedia($gallery, $media, null, $request);
    }

    /**
     * Updates a medium to a gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Retrieves the medias of specified gallery.",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type="sonata_media_api_form_gallery"))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when an error if medium cannot be found in gallery"
     *     )
     * )
     *
     * @param string  $galleryId Gallery identifier
     * @param string  $mediaId   Medium identifier
     * @param Request $request   Symfony request
     *
     * @throws NotFoundHttpException
     *
     * @return GalleryInterface
     */
    public function putGalleryMediaGalleryhasmediaAction($galleryId, $mediaId, Request $request)
    {
        $gallery = $this->getGallery($galleryId);
        $media = $this->getMedia($mediaId);

        foreach ($gallery->getGalleryHasMedias() as $galleryHasMedia) {
            if ($galleryHasMedia->getMedia()->getId() === $media->getId()) {
                return $this->handleWriteGalleryhasmedia($gallery, $media, $galleryHasMedia, $request);
            }
        }

        throw new NotFoundHttpException(sprintf('Gallery "%s" does not have media "%s"', $galleryId, $mediaId));
    }

    /**
     * Deletes a medium association to a gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Retrieves the list of galleries (paginated).",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when medium is successfully deleted from gallery"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when an error has occurred while medium deletion of gallery"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when unable to find gallery or media"
     *     )
     * )
     *
     * @param string $galleryId Gallery identifier
     * @param string $mediaId   Media identifier
     *
     * @throws NotFoundHttpException
     *
     * @return Rest\View
     */
    public function deleteGalleryMediaGalleryhasmediaAction($galleryId, $mediaId)
    {
        $gallery = $this->getGallery($galleryId);
        $media = $this->getMedia($mediaId);

        foreach ($gallery->getGalleryHasMedias() as $key => $galleryHasMedia) {
            if ($galleryHasMedia->getMedia()->getId() === $media->getId()) {
                $gallery->getGalleryHasMedias()->remove($key);
                $this->getGalleryManager()->save($gallery);

                return ['deleted' => true];
            }
        }

        return FOSRestView::create([
            'error' => sprintf('Gallery "%s" does not have media "%s" associated', $galleryId, $mediaId),
        ], 400);
    }

    /**
     * Deletes a gallery.
     *
     * @Operation(
     *     tags={"/api/media/galleries"},
     *     summary="Deletes a gallery.",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when gallery is successfully deleted"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when an error has occurred while gallery deletion"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when unable to find gallery"
     *     )
     * )
     *
     * @param string $id Gallery identifier
     *
     * @throws NotFoundHttpException
     *
     * @return Rest\View
     */
    public function deleteGalleryAction($id)
    {
        $gallery = $this->getGallery($id);

        $this->galleryManager->delete($gallery);

        return ['deleted' => true];
    }

    /**
     * Write a GalleryHasMedia, this method is used by both POST and PUT action methods.
     *
     * @return FormInterface
     */
    protected function handleWriteGalleryhasmedia(GalleryInterface $gallery, MediaInterface $media, ?GalleryHasMediaInterface $galleryHasMedia = null, Request $request)
    {
        $form = $this->formFactory->createNamed('', ApiGalleryHasMediaType::class, $galleryHasMedia, [
            'csrf_protection' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $galleryHasMedia = $form->getData();
            $galleryHasMedia->setMedia($media);

            // NEXT_MAJOR: remove this if/else block. Just call `$gallery->addGalleryHasMedia($galleryHasMedia);`
            if ($gallery instanceof GalleryMediaCollectionInterface) {
                $gallery->addGalleryHasMedia($galleryHasMedia);
            } else {
                $gallery->addGalleryHasMedias($galleryHasMedia);
            }
            $this->galleryManager->save($gallery);

            $context = new Context();
            $context->setGroups(['sonata_api_read']);
            $context->enableMaxDepth();

            $view = FOSRestView::create($galleryHasMedia);
            $view->setContext($context);

            return $view;
        }

        return $form;
    }

    /**
     * Retrieves gallery with identifier $id or throws an exception if it doesn't exist.
     *
     * @param string $id Gallery identifier
     *
     * @throws NotFoundHttpException
     *
     * @return GalleryInterface
     */
    protected function getGallery($id)
    {
        $gallery = $this->getGalleryManager()->findOneBy(['id' => $id]);

        if (null === $gallery) {
            throw new NotFoundHttpException(sprintf('Gallery not found for identifier %s.', var_export($id, true)));
        }

        return $gallery;
    }

    /**
     * Retrieves media with identifier $id or throws an exception if it doesn't exist.
     *
     * @param string $id Media identifier
     *
     * @throws NotFoundHttpException
     *
     * @return MediaInterface
     */
    protected function getMedia($id)
    {
        $media = $this->getMediaManager()->findOneBy(['id' => $id]);

        if (null === $media) {
            throw new NotFoundHttpException(sprintf('Media not found for identifier %s.', var_export($id, true)));
        }

        return $media;
    }

    /**
     * @return GalleryManagerInterface
     */
    protected function getGalleryManager()
    {
        return $this->galleryManager;
    }

    /**
     * @return MediaManagerInterface
     */
    protected function getMediaManager()
    {
        return $this->mediaManager;
    }

    /**
     * Write a Gallery, this method is used by both POST and PUT action methods.
     *
     * @param Request     $request Symfony request
     * @param string|null $id      Gallery identifier
     *
     * @return Rest\View|FormInterface
     */
    protected function handleWriteGallery($request, $id = null)
    {
        $gallery = $id ? $this->getGallery($id) : null;

        $form = $this->formFactory->createNamed('', ApiGalleryType::class, $gallery, [
            'csrf_protection' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $gallery = $form->getData();
            $this->galleryManager->save($gallery);

            $context = new Context();
            $context->setGroups(['sonata_api_read']);
            $context->enableMaxDepth();

            $view = FOSRestView::create($gallery);
            $view->setContext($context);

            return $view;
        }

        return $form;
    }
}
