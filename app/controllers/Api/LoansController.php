<?php

namespace App\Controller;

use Exception;

use Slim\Views\Twig as View;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Swagger\Annotations as SWG;
use App\Entity\LoanApplication;
use App\Resource\LoanApplicationResource;
use App\Resource\FilesResource;


/**
 * loans controller.
 *
 * @RouteResource("Loans")
 *
 * @SWG\Definition(
 *   definition="Loans",    
 *   description="list of loans",
 *   @SWG\Property(property="uuid", type="string"),
 *   @SWG\Property(property="externalRef", type="string"),
 *   @SWG\Property(property="createdAt", type="string", format="date-format"),
 * )
 */
class LoansController {
    
    protected $view;
    protected $db;
    protected $root;
    protected $client;
    protected $exception;
    protected $controls;
    protected $upload;
    protected $dir_documents = '/uploads/documents/';
    private   $token;
    private   $em;
    private   $api_easydoc;
    private   $loanApplicationResource;
    private   $filesResource;
    
    /**
     * @SWG\Info(
     *     title="API's - Documentation",
     *     description="Documentation des applications (MPC, TV Congo, ...)",
     *     version="1.0"
     * ),
     * @SWG\Swagger(
     *     schemes={"http", "https"},
     *     host="mpc-congo-backend",
     *     @SWG\SecurityScheme(
     *        name="Authorization",
     *        type="apiKey",
     *        in="header",
     *        securityDefinition="ApiKeyAuth",
     *        description="API key"
     *     )
     * ),
     */
    public function __construct(View $view, EntityManager $em, $config, $clientGuzzle, $exception, $controls, $upload, LoanApplicationResource $loanApplicationResource, FilesResource $filesResource) 
    {
       $this->view        = $view;
       $this->em          = $em;
       $this->root        = $_SERVER['DOCUMENT_ROOT'];
       $this->token       = $config['token'];
       $this->client      = $clientGuzzle;
       $this->exception   = $exception;
       $this->controls    = $controls;
       $this->upload      = $upload;
       $this->api_easydoc = $this->controls->getClient($config);
       
       $this->loanApplicationResource = $loanApplicationResource;
       $this->filesResource = $filesResource;
    }
    
    /**
     * @SWG\Get(
     *     security={
     *         {"ApiKeyAuth":{}}
     *     },
     *     tags={"Loans"},
     *     description="List of Loan Application",
     *     summary="Retrieve list of loans..",
     *     path="/loans",
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         type="integer",
     *         description="page number"
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         type="integer",
     *         description="number items per page",
     *         maxLength=2
     *     ),
     *     @SWG\Response(response="200", description="Returned when successful",
     *          @SWG\Schema(
     *              @SWG\Property(
     *                  type="array",
     *                  property="data",
     *                  description="list of loans",
     *                  @SWG\Items(ref="#/definitions/Loans")
     *              )
     *          )
     *     ),
     *     @SWG\Response(response="401", description="Expired API key"),
     *     @SWG\Response(response="404", description="Returned when loans application not exist"),
     * )
     */
    public function getLoansAction($request, $response, $args) 
    {
        if ($this->controls->checkToken($request, $this->token)) {
            $limit = $this->controls->getParam($request, $args, 'limit');
            $page  = $this->controls->getParam($request, $args, 'page');
            
            try {
              $output = $this->loanApplicationResource->getLoans($limit, $page);
              
              if (!empty($output)) {
                  return $response->withJson(
                      $output, 
                      200
                  );
              }
              else {
                  $code = 404;
                  $message = 'loans application not exit';
                  
                  return $response->withJson(
                    array(
                        'message' => $message, 
                        'code' => $code
                    ), 
                    $code
                  );
              }
            }
            catch(\Exception $e){
                return $response->withJson(array('error' => $e->getMessage()), 404);
            }
        }
        
        return $this->getResponseAuthFailed($response);
    }
    
    /**
     * @SWG\Delete(
     *     security={
     *         {"ApiKeyAuth":{}}
     *     },
     *     tags={"Loans"},
     *     description="Remove a Loan Application",
     *     summary="Remove a Loan Application",
     *     path="/loans/{id}/remove",
     *     @SWG\Response(response="204", description="Resource deleted successful"),
     *     @SWG\Response(response="404", description="Returned when loan application is not found"),
     *     @SWG\Response(response="500", description="Internal error, Deleting is not possible"),
     *     @SWG\Response(response="401", description="Expired API key"),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="string",
     *         description="Id Loan Application",
     *         required=true,
     *     )
     * )
     */
    public function deleteLoanAction($request, $response, $args) 
    {
        $fileExist = false;
        $code      = 204;
        $loanApplicationRepository = $this->getRepositoryLoanApplication();
        
        if ($this->controls->checkToken($request, $this->token)) {
            $id               = $this->controls->getParam($request, $args, 'id');
            $loanApplication  = $loanApplicationRepository->findOneByUuid($id);
            
            try{
              if (!empty($loanApplication)) {
                $message    = [];
                $files      = $this->loanApplicationResource->getFilesByLoanApplication($loanApplication->getuuid());
                $directory  = $this->root . $this->dir_documents .$loanApplication->getId();
                
                if (is_dir($directory)) {
                    array_map('unlink', glob("$directory/*.*"));
                    
                    rmdir($directory);
                }
                
                $this->loanApplicationResource->remove($loanApplication);
              }
              else {
                $code = 404;
                $message = 'loan application not found';
              }
              
              $data = array(
                  'message' => $message,
                  'code'    => $code
              );
              
              return $response->withJson($data, $code);
            }
            catch(\Exception $e){
                return $response->withJson(array('error' => $e->getMessage()), 404);
            }
        }
        
        return $this->getResponseAuthFailed($response);
    }
    
    public function getResponseAuthFailed($response)
    {
        $code   = 401;
        $output = array(
            'code'    => $code,
            'message' => "Account authentication failed"
        );
        
        return $response->withJson($output, $code);
    }
    
    private function getContents($response) 
    {
        return json_decode($response->getBody()->getContents());
    }
    
    private function getLoanApplicationManager()
    {
        return new LoanApplication();
    }
    
    private function getRepositoryLoanApplication()
    {
        return $this->em->getRepository('App\Entity\LoanApplication');
    }
}