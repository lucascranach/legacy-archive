<?php

/**
 * Class 'Metadata' is a 'Cranach Digital Archive' extension.
 * Handles the database queries for the metadata.
 * Gets and sets the metadata and provides methods for the save and edit process.
 *
 * @author Joerg Stahlmann
 * @package src/classes
 */
class Metadata
{
    protected $selectedLanguage;
    protected $con;
    protected $id;
    protected $imgName;

    /**
     * Constructor function of the class
     */
    public function __construct($id = "", $img = "")
    {
        $this->con = $this->connectDatabase();
        $this->selectedLanguage = (isset($_COOKIE['lang'])) ? $_COOKIE['lang'] : 'Englisch';

        if (empty($id)) {
            $id = isset($_SESSION['object']['uid']) ? $_SESSION['object']['uid'] : 99999999;
        }
        if (empty($img)) {
            $img = isset($_SESSION['object']['img']) ?
                $_SESSION['object']['img'] . '.tif' : "ERROR - NO IMAGE IN SESSION";
        }

        $this->id = $id;
        $this->imgName = $img;
    }

    /**
     * Create Database connection to cdaSqlMeta Table
     *
     * @return sqlCon Database connection
     */
    protected function connectDatabase()
    {
      require_once('src/classes/DbConnection.class.php');
      $dbcon = new DbConnection();
      return $dbcon->getConnection();
    }

    /**
    * Get all Metadata from cdaSqlMeta Database
    *
    * @return array metadata
    **/
    public function getMetadata()
    {
        $resultDE = array();
        $resultEN = array();

        $sql = "SELECT * FROM Metadata\n"
          . "WHERE Name LIKE '$this->imgName'";

        $ergebnis = mysqli_query($this->con, $sql);

        while ($row = mysqli_fetch_object($ergebnis)) {
            if ($row->Language == 'Deutsch') {
                $resultDE = array(
                    "file-type-de" => $row->FileType,
                    "image-description-de" => $row->ImageDesc,
                    "image-created-de" => $row->ImageCreated,
                    "image-date-de" => $row->ImageDate,
                    "image-source-de" => $row->ImageSrc
                );
            } else {
                $resultEN = array(
                    "file-type-en" => $row->FileType,
                    "image-description-en" => $row->ImageDesc,
                    "image-created-en" => $row->ImageCreated,
                    "image-date-en" => $row->ImageDate,
                    "image-source-en" => $row->ImageSrc
                );
            }
        }

        return array_merge($resultDE, $resultEN);
    }


    /**
    * Save Metadata to cdaSqlMeta Database
    *
    **/
    public function saveMetadata($data)
    {
        $fileTypeDe = (isset($data['file-type-de'])) ? $data['file-type-de'] : '';
        $imageDescDe = (isset($data['image-description-de'])) ? $data['image-description-de'] : '';
        $imageDateDe = (isset($data['image-date-de'])) ? $data['image-date-de'] : '';
        $imageCreatedDe = (isset($data['image-created-de'])) ? $data['image-created-de'] : '';
        $imageSourceDe = (isset($data['image-source-de'])) ? $data['image-source-de'] : '';
        $fileTypeEn = (isset($data['file-type-en'])) ? $data['file-type-en'] : '';
        $imageDescEn = (isset($data['image-description-en'])) ? $data['image-description-en'] : '';
        $imageDateEn = (isset($data['image-date-en'])) ? $data['image-date-en'] : '';
        $imageCreatedEn = (isset($data['image-created-en'])) ? $data['image-created-en'] : '';
        $imageSourceEn = (isset($data['image-source-en'])) ? $data['image-source-en'] : '';

        if (empty($this->getMetadata())) {
            $sql =  "INSERT INTO Metadata (Object_UId, Name, Creator, Title, Date, Filetype,\n"
            . "ImageDesc, ImageDate, ImageCreated, ImageSrc, Language)\n"
            . "VALUES ('$this->id', '$this->imgName', '', '', '', '$fileTypeDe', '$imageDescDe',\n"
            . "'$imageDateDe', '$imageCreatedDe', '$imageSourceDe',\n"
            . "'Deutsch')";

            if (!mysqli_query($this->con, $sql)) {
                die('Error: ' . mysql_error());
            }

            $sql =  "INSERT INTO Metadata (Object_UId, Name, Creator, Title, Date, Filetype,\n"
            . "ImageDesc, ImageDate, ImageCreated, ImageSrc, Language)\n"
            . "VALUES ('$this->id', '$this->imgName', '', '', '', '$fileTypeEn', '$imageDescEn',\n"
            . "'$imageDateEn', '$imageCreatedEn', '$imageSourceEn',\n"
            . "'Englisch')";

            if (!mysqli_query($this->con, $sql)) {
                die('Error: ' . mysql_error());
            }
        } else {
            $sql = "UPDATE Metadata SET Creator = '', Title = '', Date = '', FileType = '$fileTypeDe',\n"
            . "ImageDesc = '$imageDescDe', ImageDate = '$imageDateDe',\n"
            . "ImageCreated = '$imageCreatedDe', ImageSrc = '$imageSourceDe'\n"
            . "WHERE Name LIKE '$this->imgName' AND Language LIKE 'Deutsch'";

            if (!mysqli_query($this->con, $sql)) {
                die('Error: ' . mysql_error());
            }

            $sql = "UPDATE Metadata SET Creator = '', Title = '', Date = '', FileType = '$fileTypeEn',\n"
            . "ImageDesc = '$imageDescEn', ImageDate = '$imageDateEn',\n"
            . "ImageCreated = '$imageCreatedEn', ImageSrc = '$imageSourceEn'\n"
            . "WHERE Name LIKE '$this->imgName' AND Language LIKE 'Englisch'";

            if (!mysqli_query($this->con, $sql)) {
                die('Error: ' . mysql_error());
            }
        }

        return true;
    }
}
