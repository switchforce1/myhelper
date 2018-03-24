<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Switchforce1\FileBundle\Handler;

/**
 * Description of FileUploadHandler
 *
 * @author switch
 */
class FileUploadHandler 
{
    

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var string
     */
    protected $uploadDir ;

    /**
     * CustomUploadHandler constructor.
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return string
     */
    private function getDefaultDirectory()
    {
        return $this->kernel->getRootDir().'/../web/uploads/';
    }

    /**
     * @return string
     */
    private function getDefaultTempDirectory()
    {
        return $this->kernel->getRootDir().'/../web/uploads/temp/';
    }

    /**
     * @param $requestHeaders
     * @param UploadedFile $uploadedFile
     * @param null $saveDirectory
     * @return bool|null
     * @throws \Exception
     */
    public function save($requestHeaders,UploadedFile $uploadedFile, $saveDirectory = null)
    {
        if($saveDirectory === null || $saveDirectory ==""){
            $saveDirectory = $this->getDefaultDirectory();
        }
        //Enregistrement sans chunk
        if(!$this->isChunked($requestHeaders)){
            //Peut générer une Exception
            return $this->makeSimpleSave($saveDirectory, $uploadedFile);
        }

        return $this->saveChunkedFile($requestHeaders, $uploadedFile, $saveDirectory);
    }

    /**
     * Verifie si le fichier à traiter a été divisé ou pas
     * @param $requestHeaders
     * @return bool
     */
    private function isChunked($requestHeaders)
    {
        if(array_key_exists("content-range", $requestHeaders->all())){
            return true;
        }
        return false;
    }

    /**
     * @param $saveDirectoy
     * @param UploadedFile $uploadedFile
     * @param string $destinationName
     * @return bool
     * @throws \Exception
     */
    private function makeSimpleSave($saveDirectoy, UploadedFile $uploadedFile, $destinationName = "")
    {
        if(!is_string($destinationName) || $destinationName == ""){
            $destinationName = $uploadedFile->getClientOriginalName();
        }
        try{
            $uploadedFile->move(
                $saveDirectoy,
                $destinationName
            );
        }catch (\Exception $exception){
            throw new  \Exception("Echec Enrégistrement : ".$exception->getMessage());
        }
        return true;
    }

    /**
     * @param $requestHeaders
     * @param UploadedFile $uploadedFile
     * @param $saveDirectory
     * @return bool|null
     * @throws \Exception
     */
    private function saveChunkedFile($requestHeaders, UploadedFile $uploadedFile, $saveDirectory)
    {
        $chunkInfo  = $this->getChunkedInfo($requestHeaders);
        if(empty($chunkInfo)){
            return false;
        }
        //fichier en local
        $localChunkFile = $this->getChunkedSavePart($saveDirectory, $uploadedFile->getClientOriginalName());
        //Pas de fichier en local et precedente taile diféerent de 0
        if(!$localChunkFile && intval($chunkInfo['old_size'])!=0){
            return false;
        }
        
        //A la reception de la premiere tramme
        if(intval($chunkInfo['old_size']) == 0){
            return $this->makeSimpleSave($saveDirectory,$uploadedFile);
        }
        return $this->mergeTrankedFile($saveDirectory, $uploadedFile, $uploadedFile->getClientOriginalName());
    }

    /**
     * @param string $saveDirectory
     * @param UploadedFile $uploadedFile
     * @param string $filename
     * @return bool
     * @throws \Exception
     */
    private function mergeTrankedFile(string $saveDirectory, UploadedFile $uploadedFile, string $filename)
    {
        /** @var File $oldFile */
        $oldFile = $this->getChunkedSavePart($saveDirectory, $filename);
        $oldFileName  = $saveDirectory.$oldFile->getBasename();
        //var_dump($oldFileName);
        $outFile = fopen("{$oldFileName}", 1 ? "ab" : "wb");

        $this->makeSimpleSave($this->getDefaultTempDirectory(), $uploadedFile);
        $tempFileName = $this->getDefaultTempDirectory().$uploadedFile->getClientOriginalName();
        //var_dump($tempFileName);die();
        $infile = fopen($tempFileName, "rb");

        while ($buff = fread($infile, 4096)) {
            fwrite($outFile, $buff);
        }

        fclose($outFile);
        fclose($infile);

        return true;
    }

    /**
     * @param $saveDirectory
     * @param $destinationName
     * @return string
     */
    private function getChunkedSavePart($saveDirectory, $destinationName)
    {
        return $this->getPhysicalFile($saveDirectory, $destinationName);
    }

    /**
     * @param $saveDirectory
     * @param $fileName
     * @return bool
     */
    private function fileExiste($saveDirectory, $fileName)
    {
        return file_exists($saveDirectory.$fileName);
    }

    /**
     * @param $saveDirectory
     * @param $fileName
     * @return null|File
     */
    private function getPhysicalFile($saveDirectory, $fileName)
    {
        if(!$this->fileExiste($saveDirectory, $fileName)){
            return null;
        }
        return new File($saveDirectory.$fileName);
    }

    /**
     * @param $requestHeaders
     * @return null
     */
    private function getChunkedInfo($requestHeaders)
    {
        $chunkedInfo = array();
        $contentRange = $this->getContentRange($requestHeaders);
        //Si pas de Chunk
        if(!$contentRange){
            return $chunkedInfo;
        }
        //Exple Content Renge  = "bytes 0-999999/1154826"

        //["bytes 0-999999", "1154826"]
        $contentRangeParts = explode("/",$contentRange);
        //expl : 1154826
        $wholeFileSize =intval($contentRangeParts[1]);
        //["bytes","0-999999"]
        $sendSizeParts = explode(" ",$contentRangeParts[0]);
        $sizes  = explode("-",$sendSizeParts[1]);
        $oldSize  = $sizes[0];
        $currentSize  = $sizes[1];

        $chunkedInfo  = array(
            "old_size"=>$oldSize,
            "current_size"=>$currentSize,
            "whole_size"=>$wholeFileSize,
        );
        return $chunkedInfo;
    }

    /**
     * Recupère le content range à partir d'un entete
     * @param $requestHeaders
     * @return null
     */
    private function getContentRange($requestHeaders)
    {
        //Si pas de Chunk
        if(!$this->isChunked($requestHeaders)){
            return null;
        }
        return $requestHeaders->all()["content-range"][0];
    }
}
