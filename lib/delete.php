<?php
namespace OCA\ForceDelete;

use OCP\IUserSession;
class Delete {


    private $userSession;
    private $storage;
    public function __construct(IUserSession $userSession) {
		$this->userSession = $userSession;
        $this->storage = new \OC\Files\Storage\Home(array('user'=>$this->userSession->getuser()));
    }

    /**
    * Force owner's delete file/folder by admin role.
    *
    * @param string $owner
    * @param string $file
    * @return bool
    */
    public function forceDeleteFile($isdir, $file) {
        //$file = /123.txt, /4/123.txt



        $owner = $this->userSession->getUser()->getUID();
        
        $isdir = $isdir == 'true' ? true : false;
        

       //Prepare filter the file path.
       $dirs = explode("/", $file);
       $rootDir = $dirs[0];
       $filterFilePath = preg_replace("/^$rootDir\//",'',$file);

       // Step1: Init Mountpoints and new owner's view.
       //\OC\Files\Filesystem::tearDown();
        \OC\Files\Filesystem::initMountPoints($owner);
        $view = new \OC\Files\View("/$owner/files");
        $cache = $this->storage->getCache('files'.$file);
        
       
        if(!$isdir) {
       
            if (!$view->unlockFile($filterFilePath,\OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE)){
                \OCP\Util::writeLog('module name',"When force unlock file ($filterFilePath) failed 2.", \OCP\Util::ERROR);
                return false;
            }

            // Step3: Delete the file's priview file.
            $this->deletePreviewFiles($owner,$view,$file);


            // Step4: Delete the file's veriosn file.
            if(\OC_App::isEnabled('files_versions')) {
                
                \OCP\Util::writeLog('module name',"version", \OCP\Util::ERROR);
                $this->deleteVersionFiles($owner,$filterFilePath);
            }

            // Step5: Delete the file.
            if (!$this->storage->unlink('files'.$file) || $view->file_exists($filterFilePath)) {
                \OCP\Util::writeLog('module name',"When force delete $filterFilePath file failed 3.", \OCP\Util::ERROR);
                return false;
            }
        } else {
            
             // Step3: Delete the file's priview file.
            $this->deletePreviewFiles($owner,$view,$file);


            // Step4: Delete the file's veriosn file.
            if(\OC_App::isEnabled('files_versions')) {
                    
                $this->deleteVersionFiles($owner,$filterFilePath);
            }

            
            if(!$this->storage->rmdir('files'.$file)) {
                \OCP\Util::writeLog('module name',"When force delete $filterFilePath file failed 4.", \OCP\Util::ERROR);
                return false;
            } 
            
        
        }
         
        $cache->remove('files'.$file);
        return true;
    }



    
    /**
    * Force owner's delete version files by admin role.
    *
    * @param string $owner
    * @param string $file
    * @return bool
    */
    public function deleteVersionFiles($owner,$file){
       //$file = 1.txt
       //$file = 2
       //$file = 3/1.txt
       $view = new \OC\Files\View("/$owner");
       
       if($view->is_dir('files_versions/'.$file)) {
           $files = $view->getDirectoryContent('files_versions/'.$file);


           foreach($files as $fileArray) {
               $filename = $fileArray['name'];
               $filePath = $file . '/' . $filename;
               $this->deleteVersionFiles($owner,$filePath);
           }
           $this->storage->unlink('files_versions/'.$file);
           $this->storage->getCache('files_versions/'.$file)->remove('files_versions/'.$file);
       }
       else{

           $versions = \OCA\Files_Versions\Storage::getVersions($owner,$file);
           
           if (!empty($versions)) {
               foreach ($versions as $v) {
                   $this->deletePreviewFiles($owner,$view,'files_versions/'. $file . ".v".$v['version']);
                   $this->storage->unlink('files_versions/'.$file . ".v".$v['version']);
                   $this->storage->getCache('files_versions/'.$file . ".v".$v['version'])->remove('files_versions/'.$file . ".v".$v['version']);
               }
           }
       }
    }
    /**
    * Force owner's delete preview files by admin role.
    *
    * @param string $owner
    * @param string $file
    * @return bool
    */
    public function deletePreviewFiles($owner,$view,$file){
       //$file = files/1.txt
       //$file = files/2
       //$file = files/3/1.txt

       $dirs = explode("/", $file);
       $rootDir = $dirs[0];
       $filterFilePath = preg_replace("/^$rootDir\//",'',$file);

       if($view->is_dir($filterFilePath)){
           $files = $view->getDirectoryContent($filterFilePath);
           foreach($files as $fileArray) {
               $filename = $fileArray['name'];
               $filePath = $file . '/' . $filename;
               $this->deletePreviewFiles($owner,$view,$filePath);
           }
       }
       else{
           $preview = new \OC\Preview($owner, $rootDir,preg_replace("/^$rootDir\//",'',$file));
           $preview->deleteAllPreviews();
       }

    }

    
}


?>
