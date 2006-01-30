<?php
/**
 * TTarAssetDeployer class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * TTarAssetDeployer class.
 * Publish a tar file by extracting its contents to the assets directory.
 * Each tar file must be accomplished with its own MD5 check sum file.
 * The MD5 file is published when the tar contents are successfully 
 * extracted to the assets directory. The presence of the MD5 file
 * as published asset assumes that the tar file has already been extracted.
 *
 * Compressed tar files are not supported.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TTarAssetManager extends TAssetManager
{
	/**
	 * Publish a tar file by extracting its contents to the assets directory.
	 * Each tar file must be accomplished with its own MD5 check sum file.
	 * The MD5 file is published when the tar contents are successfully 
	 * extracted to the assets directory. The presence of the MD5 file
	 * as published asset assumes that the tar file has already been extracted.
	 * @param string tar filename
	 * @param string MD5 checksum for the corresponding tar file.
	 * @return string URL path to the directory where the tar file was extracted.
	 */
	public function publishTarFile($tarfile, $md5sum)
	{
		//if md5 file is published assume tar asset deployed
		if(($md5Path = $this->getPublishedUrl($md5sum)) != null)
			return dirname($md5Path);

		if(($fullpath=realpath($tarfile))===false)
			throw new TIOException('unable_to_locate_tar_file', $tarfile);
		else if(is_file($fullpath))
		{
			$dir=$this->hash(dirname($fullpath));
			$destination = $this->getBasePath().'/'.$dir.'/';
			if($this->deployTarFile($fullpath, $destination))
				return dirname($this->publishFilePath($md5sum));
		}
		
		throw new TIOException('unable_to_publish_tar_file', $tarfile);
	}

	/**
	 * Extracts the tar file to the destination directory.
	 * N.B Tar file must not be compressed.
	 * @param string tar file
	 * @param string path where the contents of tar file are to be extracted
	 * @return boolean true if extract successful, false otherwise.
	 */
	protected function deployTarFile($fullpath,$destination)
	{
		Prado::using('System.Data.TTarFileExtractor');
		$tar = new TTarFileExtractor($fullpath);
		return $tar->extract($destination);
	}
}

?>