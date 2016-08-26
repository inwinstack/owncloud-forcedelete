<?php
/**
 * ownCloud - forcedelete
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author CurlyYang <eric.y@inwinstack.com>
 * @copyright CurlyYang 2016
 */

namespace OCA\ForceDelete\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCA\ForceDelete\Delete;
class ForceDeleteController extends Controller {


	private $deleteAction;

	public function __construct($AppName, IRequest $request, Delete $deleteAction) {
		parent::__construct($AppName, $request);
		$this->deleteAction = $deleteAction;
	}

	

	/**
	 * @NoAdminRequired
	 */
	public function deleteFile($files) {
        if(array_key_exists('path',$files)) {
            return new DataResponse([$this->deleteAction->forceDeleteFile($files['isdir'], $files['path'])]);


        } else {
            $data = [];

            if(count($files) > 0) {
                foreach($files as $file) {
                   array_push($data, $this->deleteAction->forceDeleteFile($file['isdir'], $file['path']));

                }
            }

        }
		return new DataResponse($data);
	}


}
