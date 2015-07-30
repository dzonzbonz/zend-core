<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use ZCore\Service\AbstractApiService;
use ZCore\Resource\BaseResource;
/**
 * Description of AbstractApiController
 *
 * @author dzonz
 */
class AbstractApiController 
extends AbstractRestfulController {

    const CONTENT_TYPE_FORM = 'form';
    
    /**
     *
     * @var AbstractApiService
     */
    protected $service;

    /**
     *
     * @var \Zend\Mvc\Router\Http\RouteInterface
     */
    protected $router;
    
    protected $requestController;
    
    protected $requestQuery;
    
    public function __construct(AbstractApiService $service) {
        $this->service = $service;
        $this->contentTypes[self::CONTENT_TYPE_FORM] = array(
            'multipart/form-data',
        );
    }

    public function onDispatch(\Zend\Mvc\MvcEvent $e) {
        try {
            
            $this->onDispatchRouter();

            $response = $e->getResponse();

            if ($response instanceof \Zend\Http\AbstractMessage) {
                $header = \Zend\Http\Header\ContentType::fromString("Content-Type: application/json");
                $response->getHeaders()->addHeader($header);
            }

            $this->initService();

            $c = explode("\\", $this->params()->fromRoute('controller'));
            $controllerName = $c[count($c) - 1];

            $this->requestController = strtolower($controllerName);
            $this->requestQuery = $this->getRequest()->getQuery()->toArray();

            $returnObject = parent::onDispatch($e);

            if ($returnObject instanceof BaseResource) {
                $baseResourceData = $returnObject->toArray();
                $linkedResource = $returnObject->getLinkedResource();
                $linkedResourceData = array();
                if (!is_null($linkedResource)) {
                    $linkedResourceData = array("linked" => $linkedResource->toArray());
                } 
                return $this->getResponse()->setContent(json_encode($baseResourceData + $linkedResourceData));
            } else if (is_array($returnObject)) {
                return $this->getResponse()->setContent(json_encode($returnObject));
            } else {
                return $returnObject;
            }   
        }
        catch (\ZCore\Exception\InvalidInputDataException $ide) {
            return $this->prepareErrorResponse(
                'INVALID_INPUT_DATA', 
                $ide->getInputFilter()->getMessages(), 
                400
            );
        } 
        catch (\ZCore\Exception\EntryNotFoundException $enf) {
            return $this->prepareErrorResponse(
                'ENTRY_NOT_FOUND', 
                $enf->getMessage(), 
                404
            );
        }
        catch (\ZCore\Exception\DuplicateEntryException $enf) {
            return $this->prepareErrorResponse(
                'DUPLICATE_ENTRY', 
                $enf->getMessage(), 
                409
            );
        } 
        catch (\Exception $ex) {
            return $this->prepareErrorResponse(
                'UNKNOWN_ERROR', 
                $ex->getMessage(), 
                400
            );
        }
    }
    
    protected function onDispatchRouter() {
        BaseResource::setGlobalRouter($this->getRouter());
    }
    
    protected function initService() {
        $this->service->init();
    }
    
    /**
     * 
     * @return \Zend\Mvc\Router\RouteInterface
     */
    protected function getRouter() {
        if (!$this->router) {
            $this->router = $this->service->getServiceLocator()->get('Router');
        }
        
        return $this->router;
    }
    
    protected function assembleRoute($routeName, array $params = array()) {
        return $this->getRouter()->assemble($params, array('name' => $routeName));
    }
    
    protected function prepareErrorResponse($code, $message, $httpStatusCode = 400) {
        $resource = new \ZCore\Resource\ErrorResource();
        $resource->setData(array(
            'code' => $code,
            'message' => $message
        ));
        
        return $this->getResponse()
                ->setStatusCode($httpStatusCode)
                ->setContent(json_encode($resource->toArray()));
    }
    
    public function init() {
        $this->service->init();
    }

    protected function processBodyContent($request) {
        if ($this->requestHasContentType($request, self::CONTENT_TYPE_FORM)) {
            return $this->processMultiPartFormData($request);
        } else {
            return parent::processBodyContent($request);
        }
    }
    
    protected function processMultiPartFormData($request) {
        $a_data = array();
        
        $input = $request->getContent();
        
        $contentType = $request->getHeaders()->get('content-type');
        $contentTypeParameters = $contentType->getParameters();
        $boundary = isset($contentTypeParameters['boundary'])
                    ? $contentTypeParameters['boundary']
                    : false;

        if (!$boundary) {
            // we expect regular puts to containt a query string containing data
            return parent::processBodyContent($request);
        }
        
        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);

        // loop data blocks
        foreach ($a_blocks as $id => $block) {
            if (empty($block))
                continue;

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== FALSE) {
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
                $a_data['files'][$matches[1]] = $matches[2];
            }
            // parse all other fields
            else {
                // match "name" and optional value in between newline sequences
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
                $a_data[$matches[1]] = $matches[2];
            }
        }
        
        return $a_data;
    }

}
