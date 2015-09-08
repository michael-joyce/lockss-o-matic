<?php

/*
 * The MIT License
 *
 * Copyright (c) 2014 Mark Jordan, mjordan@sfu.ca.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace LOCKSSOMatic\SWORDBundle\Controller;

use Doctrine\ORM\EntityManager;
use LOCKSSOMatic\CRUDBundle\Entity\Aus;
use LOCKSSOMatic\CRUDBundle\Entity\Content;
use LOCKSSOMatic\CRUDBundle\Entity\ContentProviders;
use LOCKSSOMatic\CRUDBundle\Entity\DepositBuilder;
use LOCKSSOMatic\CRUDBundle\Entity\Deposits;
use LOCKSSOMatic\SWORDBundle\Event\DepositContentEvent;
use LOCKSSOMatic\SWORDBundle\Event\ServiceDocumentEvent;
use LOCKSSOMatic\SWORDBundle\Exceptions\BadRequestException;
use LOCKSSOMatic\SWORDBundle\Exceptions\DepositUnknownException;
use LOCKSSOMatic\SWORDBundle\Exceptions\HostMismatchException;
use LOCKSSOMatic\SWORDBundle\Exceptions\MaxUploadSizeExceededException;
use LOCKSSOMatic\SWORDBundle\Exceptions\OnBehalfOfMissingException;
use LOCKSSOMatic\SWORDBundle\Exceptions\TargetOwnerUnknownException;
use LOCKSSOMatic\SWORDBundle\Listener\SWORDErrorListener;
use LOCKSSOMatic\SWORDBundle\SWORDEvents;
use LOCKSSOMatic\SWORDBundle\Utilities\Namespaces;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for all SWORD requests. Error handling is done through
 * subclasses of LOCKSSOMatic\SWORDBundle\Exceptions\ApiException which are caught
 * in an event listener and handled appropriately.
 *
 * The event listener also handles unknown exceptions.
 *
 * @see SWORDErrorListener
 */
class DefaultController extends Controller
{

    /**
     *
     * @var Namespaces
     */
    private $namespaces;

    /**
     * Construct the controller.
     */
    public function __construct()
    {
        $this->namespaces = new Namespaces();
    }

    /**
     * Get the value of the X-On-Behalf-Of header (or it's equivalent), and
     * return it. Returns null if the header is not present, or is not a number.
     *
     * @param Request $request
     *
     * @return int the header value or null if not present.
     */
    private function getOnBehalfOfHeader(Request $request)
    {
        $headers = array(
            'x-on-behalf-of',
            'on-behalf-of'
        );
        foreach ($headers as $h) {
            if ($request->headers->has($h)) {
                return $request->headers->get($h);
            }
        }
        foreach ($headers as $h) {
            if ($request->query->has($h)) {
                return $request->query->get($h);
            }
        }
        throw new OnBehalfOfMissingException();
    }

    /**
     * Get a SimpleXMLElement from a string, and assign the necessary
     * xpath namespaces.
     *
     * @param string $string
     * @return SimpleXMLElement
     */
    private function getSimpleXML($string)
    {
        $xml = new SimpleXMLElement($string);
        $this->namespaces->registerNamespaces($xml);
        return $xml;
    }

    /**
     * Get a content provider for a UUID.
     *
     * @param string $uuid
     * @return ContentProvider
     *
     * @throws TargetOwnerUnknownException if the uuid does not match any provider.
     */
    private function getContentProvider($uuid = null)
    {
        $contentProvider = $this
            ->getDoctrine()
            ->getRepository('LOCKSSOMaticCRUDBundle:ContentProviders')
            ->findOneBy(array('uuid' => $uuid));
        if ($contentProvider === null) {
            throw new TargetOwnerUnknownException();
        }

        return $contentProvider;
    }

    /**
     * Get a deposit for a UUID
     *
     * @param type $uuid
     * @return type
     *
     * @throws DepositUnknownException if the UUID does not match any deposit.
     */
    private function getDeposit($uuid = null)
    {
        $deposit = $this
            ->getDoctrine()
            ->getRepository('LOCKSSOMatic\CRUDBundle\Entity\Deposits')
            ->findOneBy(array('uuid' => $uuid));

        if ($deposit === null) {
            throw new DepositUnknownException();
        }

        return $deposit;
    }

    /**
     * Ensure that a deposit matches a content provider.
     *
     * @param Deposits $deposit
     * @param ContentProviders $contentProvider
     *
     * @throws BadRequestException if the deposit does not belong to the provider.
     */
    private function matchDepositToProvider(Deposits $deposit, ContentProviders $contentProvider)
    {
        if ($deposit->getContentProvider()->getId() !== $contentProvider->getId()) {
            throw new BadRequestException(
            'Deposit or Content Provider incorrect. The '
            . 'requested deposit does not belong to the requested content provider.'
            );
        }
    }

    /**
     * Get a content entity from the database, based on a deposit and URL.
     *
     * @param Deposits $deposit
     * @param string $url
     * @return Content
     *
     * @throws BadRequestException if the content cannot be found.
     */
    private function getContent(Deposits $deposit, $url)
    {
        $content = $this
            ->getDoctrine()
            ->getRepository('LOCKSSOMaticCRUDBundle:Content')
            ->findOneBy(array(
            'url'     => $url,
            'deposit' => $deposit,
        ));
        if ($content === null) {
            throw new BadRequestException('Content item not in database: ' . $url);
        }

        return $content;
    }

    /**
     * Controller for the SWORD Service Document request.
     *
     * The 'On-Behalf-Of' request header identifies the content provider.
     * This value is also used for the collection ID (in other words, each
     * content provider has its own SWORD collection).
     *
     * @return Response The Service Document.
     */
    public function serviceDocumentAction(Request $request)
    {
        $onBehalfOf = $this->getOnBehalfOfHeader($request);

        $contentProvider = $this->getContentProvider($onBehalfOf);

        $xml = $this->getSimpleXML('<root/>');
        $event = new ServiceDocumentEvent($xml);
        /** @var EventDispatcher */
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(SWORDEvents::SERVICEDOC, $event);

        $response = $this->render(
            'LOCKSSOMaticSWORDBundle:Default:serviceDocument.xml.twig',
            array(
            'contentProvider' => $contentProvider,
            'xml'             => $xml
            )
        );
        $response->headers->set('Content-type', 'text/xml');
        return $response;
    }

    /**
     * Controller for the Col-IRI (create resource) request.
     *
     * @param integer $collectionID The SWORD Collection ID (same as the original On-Behalf-Of value).
     * @return string The Deposit Receipt response.
     */

    /**
     * Controller for the Col-IRI (create resource) request.
     *
     * @param Request $request
     * @param type $contentProviderId
     * @return type
     *
     * @throws BadRequestException
     * @throws HostMismatchException
     * @throws MaxUploadSizeExceededException
     */
    public function createDepositAction(Request $request, $contentProviderId)
    {
        $em = $this->getDoctrine()->getManager();

        // Query the ContentProvider entity so we can get its name.
        $contentProvider = $this->getContentProvider($contentProviderId);

        $atomEntry = $this->getSimpleXML($request->getContent());
        if (count($atomEntry->xpath('//lom:content')) === 0) {
            throw new BadRequestException(
            'Empty deposits not allowed. At least one '
            . 'lom:content element is required in a deposit.'
            );
        }

        // precheck the deposit
        $permissionHost = $contentProvider->getPermissionHost();
        foreach ($atomEntry->xpath('//lom:content') as $contentChunk) {
            $chunk = preg_replace('/\s*/', '', (string) $contentChunk);
            $contentHost = parse_url((string) $chunk, PHP_URL_HOST);
            if ($permissionHost !== $contentHost) {
                throw new HostMismatchException();
            }

            if ($contentChunk->attributes()->size > $contentProvider->getMaxFileSize()) {
                throw new MaxUploadSizeExceededException('Content size ' . 
                    $contentChunk->attributes()->size . 
                    ' is larger than ' . $contentProvider->getMaxFileSize());
            }
        }

        $pluginAttr = $atomEntry->xpath('lom:plugin/@name');
        if (count($pluginAttr)) {
            $pluginName = (string)$pluginAttr[0];            
        } else {
            $pluginName = 'lomplugin.aus.size';
        }
        
        $depositBuilder = new DepositBuilder();
        $deposit = $depositBuilder->fromSimpleXML($atomEntry);
        $deposit->setContentProvider($contentProvider);
        $em->persist($deposit);
        
        /** @var EventDispatcher */
        $dispatcher = $this->get('event_dispatcher');

        // Parse lom:content elements.
        foreach ($atomEntry->xpath('//lom:content') as $contentChunk) {
            $event = new DepositContentEvent($pluginName, $deposit, $contentProvider, $contentChunk);
            $dispatcher->dispatch(SWORDEvents::DEPOSITCONTENT, $event);
        }
        $em->flush();

        $response = $this->renderDepositReceipt($contentProvider, $deposit);
        $editIri = $this->get('router')->generate(
            'lockssomatic_deposit_receipt',
            array(
            'contentProviderId' => $contentProviderId,
            'uuid'              => $deposit->getUuid()
            ),
            true
        );
        $response->headers->set('Location', $editIri);
        $response->setStatusCode(201);
        return $response;
    }

    /**
     * Returns a deposit receipt, in response to a request to the SWORD Edit-IRI.
     *
     * @param integer $collectionId The SWORD Collection ID (same as the original On-Behalf-Of value).
     * @param string $uuid The UUID of the resource as provided by the content provider on resource creation.
     * @return string The Deposit Receipt response.
     */
    public function depositReceiptAction($contentProviderId, $uuid)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        $contentProvider = $this->getContentProvider($contentProviderId);
        $deposit = $this->getDeposit($uuid);
        $this->matchDepositToProvider($deposit, $contentProvider);

        return $this->renderDepositReceipt($contentProvider, $deposit);
    }

    /**
     * Render and return a deposit receipt. This isn't a controller action, but is used by
     * controller actions.
     *
     * @param type $contentProvider
     * @param type $deposit
     * @return type
     */
    private function renderDepositReceipt($contentProvider, $deposit)
    {
        // @TODO this should be a call to render depsoitReceiptAction() or something.
        // Return the deposit receipt.
        $response = $this->render(
            'LOCKSSOMaticSWORDBundle:Default:depositReceipt.xml.twig',
            array(
            'contentProvider' => $contentProvider,
            'deposit'         => $deposit
            )
        );
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * Controller for the SWORD Statement request.
     *
     * @param integer $contentProviderId The SWORD Collection ID (same as the original On-Behalf-Of value).
     * @param string $uuid The UUID of the deposit as provided by the content provider on resource creation.
     * @return string The Statement response.
     */
    public function swordStatementAction($contentProviderId, $uuid)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        $contentProvider = $this->getContentProvider($contentProviderId);
        $deposit = $this->getDeposit($uuid);
        $this->matchDepositToProvider($deposit, $contentProvider);

        if($contentProvider->getPln()) {
            $boxes = $contentProvider->getPln()->getBoxes();
        } else {
            $boxes = array();
        }
        $content = $deposit->getContent();

        $status = array();
        foreach ($content as $item) {
            foreach ($boxes as $box) {
                $status[$item->getId()][$box->getId()] = 'unknown';
                // get the item's status from the box via http request.
            }
        }

        return $this->render(
                'LOCKSSOMaticSWORDBundle:Default:swordStatement.xml.twig',
                array(
                'contentProvider' => $contentProvider,
                'boxes'           => $boxes,
                'deposit'         => $deposit,
                'content'         => $content,
                'status'          => $status
                ), $response
        );
    }

    /**
     * Get an ATOM XML representation of the deposit, suitable for PUTting
     * after an edit.
     *
     * Section 6.4 of the SWORD spec.
     *
     * @param integer $contentProviderId
     * @param string $uuid
     */
    public function viewDepositAction($contentProviderId, $uuid)
    {
        $contentProvider = $this->getContentProvider($contentProviderId);
        $deposit = $this->getDeposit($uuid);
        $this->matchDepositToProvider($deposit, $contentProvider);

        $response = $this->render(
            'LOCKSSOMaticSWORDBundle:Default:viewDeposit.xml.twig',
            array(
            'contentProvider' => $contentProvider,
            'deposit'         => $deposit
            )
        );
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * Controller for the Edit-IRI request.
     * Section 6.5.2 of SWORDv2-Profile
     *
     * http://swordapp.github.io/SWORDv2-Profile/SWORDProfile.html#protocoloperations_editingcontent_metadata
     *
     * LOCKSS-O-Matic supports only one edit operation: content providers can change the
     * value of the 'recrawl' attribute to indicate that LOM should not recrawl the content.
     *
     * HTTP 200 (OK) meaning AU config stanzas have been updated.
     * HTTP 204 (No Content) if there is no matching Content URL.
     *
     * @param integer $collectionID The SWORD Collection ID (same as the original On-Behalf-Of value).
     * @param string $uuid The UUID of the resource as provided by the content provider on resource creation.
     * @return object The Edit-IRI response.
     */
    public function editDepositAction(Request $request, $contentProviderId, $uuid)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        $contentProvider = $this->getContentProvider($contentProviderId);
        $deposit = $this->getDeposit($uuid);
        $this->matchDepositToProvider($deposit, $contentProvider);

        $atomEntry = $this->getSimpleXML($request->getContent());
        if (count($atomEntry->xpath('//lom:content')) === 0) {
            throw new BadRequestException('Empty deposits not allowed. At least one lom:content element is required in a deposit.');
        }

        $updated = 0;
        foreach ($atomEntry->xpath('//lom:content') as $contentChunk) {
            try {
                $content = $this->getContent($deposit, (string) $contentChunk);
            } catch (Exception $e) {
                continue;
                // this is not an exception according to the SWORD spec.
                // Well, 6.5.2 of the SWORD spec is silent on the issue.
            }
            $recrawl = $contentChunk[0]->attributes()->recrawl;
            $content->setRecrawl($recrawl === 'true');
            $updated++;
        }

        $this->getDoctrine()->getManager()->flush();
        if ($updated > 0) {
            return new Response('', Response::HTTP_OK);
        } else {
            return new Response('', Response::HTTP_NO_CONTENT);
        }
    }

}
