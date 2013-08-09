<?php
/**
 * This plugin is used to deposit learning objects in repositories
 *
 * @since       2.0
 * @package     repository_sword_upload
 * @copyright   2013 Jonathan Alba Videira e Marcelo Augusto Rauh Schmitt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/repository/sword_upload/utils.php');

/**
 * repository_sword_upload class
 * This is a class used send files to repositories
 *
 * @since       2.0
 * @package     repository_sword_upload
 * @copyright   2013 Jonathan Alba Videira e Marcelo Augusto Rauh Schmitt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property SWORDAPPClient $swordAppClient
 */
ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);

class repository_sword_upload extends repository {

    private $swordAppClient;
    private $serviceDocument;
    private $collections;
    private $etapa;
    private $item;

    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $SESSION, $CFG;
        parent::__construct($repositoryid, $context, $options);

        require_once($CFG->dirroot . '/repository/sword_upload/sword1/swordappclient.php');
        $this->swordAppClient =new SWORDAPPClient();

        $action = optional_param('s_action','',PARAM_RAW);

        if (optional_param('action','',PARAM_RAW) == 'upload') {
            $action = 'deposit-upload';
        }
       // echo $action.' | ';
       // echo optional_param('action','',PARAM_RAW); exit;
        if (isset($action) AND !empty($action)) {
            //echo 'entrou no if4'; exit;
            switch ($action) {

                case 'deposit-metadata':
                    $this->deposit_metadata();
                    break;

                case 'deposit-link':
                    $this->deposit_link();
                    break;

            }

            //unset($SESSION->etapa);

        } else {
            //echo 'entrou no if 3';exit;
            $this->logout();
        }

    }

    public function print_login($nao_login = true) {

        global $SESSION;
        $ret = array();
        $form = array();

      //  if (isset($SESSION->etapa) OR !$nao_login) {

            $username = new stdClass();
            $username->type = 'text';
            $username->id   = 's_username';
            $username->name = 's_username';
            $username->label = get_string('username', 'repository_sword_upload');
	    $username->maxlength = '16';
            $form[] = $username;

            $password = new stdClass();
            $password->type = 'password';
            $password->id   = 's_password';//campo id do input de busca do form
            $password->name = 's_password'; //campo name do input de busca do form
            $password->label = get_string('password', 'repository_sword_upload');
            $form[] = $password;

            $action = new stdClass();
            $action->type = 'hidden';
            $action->id   = 's_action';
            $action->name = 's_action';
            $action->value= 'login';
            $form[] = $action;

            $ret['login_btn_label'] = get_string('send', 'repository_sword_upload');

 /*       }
        else {
            $SESSION->etapa = 'instructions';
            //echo get_string('instructions', 'repository_sword_upload'); exit;
            $instructions = new stdClass();
            $instructions->type = 'hidden';
            $instructions->id = 'div-instructions';
            $instructions->name = 's_action';
            $instructions->value = 'instructions';
            $instructions->label = getInstructions();
            //echo $INSTRUCTIONS_TEXT; exit;
            //print_r(licences_select_moodle()); exit;
            $form[] = $instructions;
            $ret['login_btn_label'] = get_string('next', 'repository_sword_upload');
        } */

        $ret['login'] = $form;
        return $ret;
    }

    public function check_login() {
        return $this->login();
    }

    private function login() {
        global $SESSION;

        if (isset($SESSION->collections)) {
            return true;
        } else {
            $SESSION->username   = optional_param('s_username', '', PARAM_RAW);
            $SESSION->password   = optional_param('s_password', '', PARAM_RAW);

            if (!empty($SESSION->username) AND !empty($SESSION->password)) {
                $this->serviceDocument = $this->swordAppClient->servicedocument($this->options['sword_url'], $SESSION->username, $SESSION->password, $SESSION->username);
                if ($this->serviceDocument->sac_status == 200) {

                    $SESSION->collections= array();
                    foreach ($this->serviceDocument->sac_workspaces as $workspace){
                        if (!empty($workspace->sac_collections)) {
                            foreach ($workspace->sac_collections as $collection) {
                                $SESSION->collections[] = array(
                                    'title' => $collection->sac_colltitle,
                                    'url' => substr($collection->sac_href->asXML(),7,-1),
                                );
                            }
                        }
                    }

                    $SESSION->etapa = 'deposit-metadata';
                    return true;

                }
            }
            //echo ' entrou no login | ';
            // echo ''
            if (isset($SESSION->etapa) AND $SESSION->etapa == 'instructions') {
              //  echo ' entrou no if | ';
                return false;
            } else {
               // echo ' entrou no else | ';
                unset($SESSION->username);
                unset($SESSION->password);
                unset($SESSION->collections);
                unset($SESSION->etapa);
                unset($SESSION->entry);
                unset($this->serviceDocument);
                return false;
            }

            //unset($SESSION->username);
            //unset($SESSION->password);
            //unset($SESSION->collections);
            //unset($SESSION->etapa);
            //unset($SESSION->entry);
            //unset($this->serviceDocument);
           


        }
    }

    public function logout() {
        global $SESSION;

        unset($SESSION->username);
        unset($SESSION->password);
        unset($SESSION->collections);
        unset($SESSION->etapa);
        //echo ' entrou no logout | (valor do $SESSION->etapa:'.$SESSION->etapa;
        unset($SESSION->entry);
        unset($this->serviceDocument);
        $this->print_login(false);
    }

    /**
    * Get a file list from alfresco
    *
    * @param string $uuid a unique id of directory in alfresco
    * @param string $path path to a directory
    * @return array
    */
    public function get_listing($uuid = '', $path = '') {

        global $SESSION, $CFG;

        $ret = array();
        $ret['nosearch'] = true;
        $ret['norefresh'] = true;
        //$ret['nologin'] = false;
        //$ret['logouttext'] = 'Logout';
        $ret['login_btn_label'] = get_string('next', 'repository_sword_upload');

	$this->get_link("http://poa.ifrs.edu.br");
        switch ($SESSION->etapa) {

            case 'deposit-metadata':
                $ret['login'] = $this->print_deposit_metadata();
                break;

            case 'deposit-upload':
                $ret['upload'] = array(
                    'id' => 'file',
                    'label' => get_string('file', 'repository_sword_upload')
                );
                break;

            case 'deposit-link':
                $ret['login'] =  $this->print_deposit_link();
                break;

            case 'deposit-process':
                $list = array();
                $list[] = $this->deposit_process();
                $ret['list'] = $list;
	
                return $ret;
                break;

            default:
                break;

        }

        return $ret;
    }

    private function print_deposit_metadata() {

        global $SESSION;

        $form = array();

        $title = new stdClass();
        $title->type = 'text';
        $title->id = 's_title';
        $title->name = 's_title';
        $title->label = get_string('title', 'repository_sword_upload');
        $form[] = $title;

        $abstract = new stdClass();
        $abstract->type = 'text';
        $abstract->id = 's_abstract';
        $abstract->name = 's_abstract';
        $abstract->label = get_string('abstract', 'repository_sword_upload');
        $form[] = $abstract;

        $description = new stdClass();
        $description->type = 'text';
        $description->id = 's_description';
        $description->name = 's_description';
        $description->label = get_string('description', 'repository_sword_upload');
        $form[] = $description;

        $type = new stdClass();
        $type->type = 'select';
        $type->id = 's_type';
        $type->name = 's_type';
        $type->label = get_string('type', 'repository_sword_upload');
        $type->options = array(
            (object)array(
                'value' => 'Animation',
                'label' => 'Animation'
            ),
            (object)array(
                'value' => 'Article',
                'label' => 'Article'
            ),
            (object)array(
                'value' => 'Book',
                'label' => 'Book'
            ),
            (object)array(
                'value' => 'Book chapter',
                'label' => 'Book chapter'
            ),
            (object)array(
                'value' => 'Dataset',
                'label' => 'Dataset'
            ),
            (object)array(
                'value' => 'Learning Object',
                'label' => 'Learning Object'
            ),
            (object)array(
                'value' => 'Image',
                'label' => 'Image'
            ),
            (object)array(
                'value' => 'Image, 3-D',
                'label' => 'Image, 3-D'
            ),
            (object)array(
                'value' => 'Map',
                'label' => 'Map'
            ),
            (object)array(
                'value' => 'Musical Score',
                'label' => 'Musical Score'
            ),
            (object)array(
                'value' => 'Plan or blueprint',
                'label' => 'Plan or blueprint'
            ),
            (object)array(
                'value' => 'Preprint',
                'label' => 'Preprint'
            ),
            (object)array(
                'value' => 'Presentation',
                'label' => 'Presentation'
            ),
            (object)array(
                'value' => 'Recording, acoustical',
                'label' => 'Recording, acoustical'
            ),
            (object)array(
                'value' => 'Recording, musical',
                'label' => 'Recording, musical'
            ),
            (object)array(
                'value' => 'Recording, oral',
                'label' => 'Recording, oral'
            ),
            (object)array(
                'value' => 'Software',
                'label' => 'Software'
            ),
            (object)array(
                'value' => 'Technical Report',
                'label' => 'Technical Report'
            ),
            (object)array(
                'value' => 'Thesis',
                'label' => 'Thesis'
            ),
            (object)array(
                'value' => 'Video',
                'label' => 'Video'
            ),
            (object)array(
                'value' => 'Working Paper',
                'label' => 'Working Paper'
            ),
            (object)array(
                'value' => 'Other',
                'label' => 'Other'
            ),
        );
        $form[] = $type;



        $subject = new stdClass();
        $subject->type = 'text';
        $subject->id = 's_subject';
        $subject->name = 's_subject';
        $subject->label = get_string('subject', 'repository_sword_upload');
        $form[] = $subject;

        $language = new stdClass();
        $language->type = 'select';
        $language->label = get_string('language', 'repository_sword_upload');
        $language->name = 's_language';
        $language->id = 's_language';
        $language->options = array(
            (object)array(
                'value' => 'pt_BR',
                'label' => 'Português'
            ),
            (object)array(
                'value' => 'en_US',
                'label' => 'English (United States)'
            ),
            (object)array(
                'value' => 'en',
                'label' => 'English'
            ),
            (object)array(
                'value' => 'es',
                'label' => 'Spanish'
            ),
            (object)array(
                'value' => 'de',
                'label' => 'German'
            ),
            (object)array(
                'value' => 'fr',
                'label' => 'French'
            ),
            (object)array(
                'value' => 'it',
                'label' => 'Italian'
            ),
            (object)array(
                'value' => 'ja',
                'label' => 'Japanese'
            ),
            (object)array(
                'value' => 'zh',
                'label' => 'Chinese'
            ),
            (object)array(
                'value' => 'other',
                'label' => '(Other)'
            ),
        );
        $form[] = $language;

        $collection = new stdClass();
        $collection->type = 'select';
        $collection->label = get_string('collection', 'repository_sword_upload');
        $collection->name = 's_collection';
        $collection->id = 's_collection';
        $collections = array();
        foreach($SESSION->collections as $c) {
            $collections[] = (object)array(
                'value' => $c['url'],
                'label' => $c['title']
            );
        }
        $collection->options = $collections;
        $form[] = $collection;

        $content = new stdClass();
        $content->type = 'select';
        $content->label = get_string('content', 'repository_sword_upload');
        $content->name = 's_content';
        $content->id = 's_content';
        $content->options = array(
            (object)array(
                'value' => 'file',
                'label' => get_string('upload-file', 'repository_sword_upload')
            ),
(object)array(
                'value' => 'url',
                'label' => get_string('upload-url', 'repository_sword_upload')
            )
        );
        $form[] = $content;

        $action = new stdClass();
        $action->type = 'hidden';
        $action->id = 's_action';
        $action->name = 's_action';
        $action->value = 'deposit-metadata';
        $form[] = $action;

        return $form;
    }

    private function print_deposit_link() {

        global $SESSION;

        $form = array();

        $author = new stdClass();
        $author->type = 'text';
        $author->id = 's_author';
        $author->name = 's_author';
        $author->label = get_string('author', 'repository_sword_upload');
        $form[] = $author;

        $url = new stdClass();
        $url->type = 'text';
        $url->id = 's_url';
        $url->name = 's_url';
        $url->label = get_string('url', 'repository_sword_upload');
        $form[] = $url;

        $license = new stdClass();
        $license->type = 'select';
        $license->label = get_string('license', 'repository_sword_upload');
        $license->name = 's_license';
        $license->id = 's_license';
        $license->options = licences_select_moodle();
        $form[] = $license;

        $action = new stdClass();
        $action->type = 'hidden';
        $action->id = 's_action';
        $action->name = 's_action';
        $action->value = 'deposit-link';
        $form[] = $action;

        return $form;
    }

    private function deposit_metadata() {

        global $SESSION;

        $title = trim(optional_param('s_title','',PARAM_RAW));
        $abstract = trim(optional_param('s_abstract','',PARAM_RAW));
        $description = trim(optional_param('s_description','',PARAM_RAW));
        $type = trim(optional_param('s_type','',PARAM_RAW));
        $subject = trim(optional_param('s_subject','',PARAM_RAW));
        $language = trim(optional_param('s_language','',PARAM_RAW));
        $collection = trim(optional_param('s_collection','',PARAM_RAW));
        $content = trim(optional_param('s_content','',PARAM_RAW));

        if (!empty($title) AND !empty($abstract) AND !empty($collection) AND !empty($content) AND !empty($language) AND !empty($type) AND !empty($subject)) {
            $types = explode(';',$type);
            $subjects = explode(';',$subject);

            $SESSION->entry = array(
                'title' => $title,
                'abstract' => $abstract,
                'description' => $description,
                'type' => $types,
                'language' => $language,
                'subject' => $subjects,
                'collection' => $collection,
                'content' => $content
            );

            if ($SESSION->entry['content'] == 'url') {
                $SESSION->etapa = 'deposit-link';
            } else {
                $SESSION->etapa = 'deposit-upload';
            }

        } else {
            $SESSION->etapa = 'deposit-metadata';
        }

    }

    private function deposit_link() {

        global $SESSION;

        $url = trim(optional_param('s_url','',PARAM_RAW));
        $license = trim(optional_param('s_license','',PARAM_RAW));
        $author = trim(optional_param('s_author','',PARAM_RAW));

        if (!empty($url) AND !empty($license) AND !empty($author)) {
            $authors = explode(';',$author);

            $SESSION->entry['url'] = $url;
            $SESSION->entry['license-name'] = getLicenseTerm($license,'name-repositorio');
            $SESSION->entry['license-uri'] = getLicenseTerm($license,'uri');
            $SESSION->entry['author'] = $authors;

            $SESSION->etapa = 'deposit-process';

        } else {
            $SESSION->etapa = 'deposit-link';
        }

    }

    private function deposit_process() {

        global $SESSION, $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/repository/sword_upload/sword1/packager_mets_swap.php');

        $swordPackager = new PackagerMetsSwap($CFG->dirroot . '/repository/sword_upload/temp','files',$CFG->dirroot . '/repository/sword_upload/temp','mets_swap_package.zip');

        $swordPackager->setTitle($SESSION->entry['title']);
        $swordPackager->setAbstract($SESSION->entry['abstract']);
        $swordPackager->setDescription($SESSION->entry['description']);
        $swordPackager->setLanguage($SESSION->entry['language']);
        if (!empty($SESSION->entry['license-uri'])) {
            $swordPackager->addRights($SESSION->entry['license-name']);
            //$swordPackager->setRightsUri($SESSION->entry['license-uri']);
        }


        foreach ($SESSION->entry['author'] as $author) {
            $swordPackager->addCreator($author);
        }

        foreach ($SESSION->entry['type'] as $type) {
            $swordPackager->addTypes($type);
        }
		
	foreach ($SESSION->entry['subject'] as $subject) {
            $swordPackager->addSubject($subject);
        }

        $swordPackager->addIdentifier($SESSION->entry['url']);
        //$swordPackager->setIdentifierUri($SESSION->entry['url']);

        $swordPackager->create();

        $testdr = $this->swordAppClient->deposit(
            $SESSION->entry['collection'],
            $SESSION->username,
            $SESSION->password,
            $SESSION->username,
            $CFG->dirroot . '/repository/sword_upload/temp/mets_swap_package.zip',
            'http://purl.org/net/sword-types/METSDSpaceSIP',
            'application/zip'
            );

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {

            copy($CFG->dirroot . '/repository/sword_upload/temp/files/mets.xml',$CFG->dirroot . '/repository/sword_upload/temp/files/mets-bak.xml');
            @unlink($CFG->dirroot . '/repository/sword_upload/temp/files/mets.xml');
            @unlink($CFG->dirroot . '/repository/sword_upload/temp/mets_swap_package.zip');

            $SESSION->etapa = 'finish';

            return array(
                'title' => $SESSION->entry['title'],
                'url' => $SESSION->entry['url'],
                'source' => $SESSION->entry['url'],
		        'thumbnail' => $this->get_thumbnail($SESSION->entry['url']),
            );

        } else {
            throw new moodle_exception('upload_error','sword_upload_repositpry');
        }

    }

    //Verifica a extensao do arquivo para definir o icone que deve aparecer
    private function get_thumbnail($element) {
        global $OUTPUT;

        $parts =explode(".", $element);
        $extension = end($parts);
        $extension = strtolower($extension);

        switch($extension) :
            case 'doc':
            case 'odt':
            case 'docx':
            case 'dot':
            case 'dotx':
                $thumbnail = $OUTPUT->pix_url('f/document-32')->out(false);
                break;

            case 'ppt':
            case 'pptx':
            case 'odp':
            case 'pps':
            case 'ppsx':
                $thumbnail = $OUTPUT->pix_url('f/powerpoint-32')->out(false);
                break;

            case 'xls':
            case 'xlsx':
            case 'ods':
                $thumbnail = $OUTPUT->pix_url('f/calc-32')->out(false);
                break;

            case 'zip':
            case 'rar':
                $thumbnail = $OUTPUT->pix_url('f/archive-32')->out(false);
                break;

            case 'pdf':
                $thumbnail = $OUTPUT->pix_url('f/pdf-32')->out(false);
                break;

            case 'txt':
                $thumbnail = $OUTPUT->pix_url('f/text-32')->out(false);
                break;

            case 'avi':
            case 'mov':
                $thumbnail = $OUTPUT->pix_url('f/avi-32')->out(false);
                break;

            case 'swf':
            case 'fla':
                $thumbnail = $OUTPUT->pix_url('f/flash-32')->out(false);
                break;

            case 'jpg':
            case 'gif':
                $thumbnail = $OUTPUT->pix_url('f/image-32')->out(false);
                break;

            case 'mp3':
            case 'wav':
                $thumbnail = $OUTPUT->pix_url('f/audio-32')->out(false);
                break;

            case 'html':
            case 'htm':
            case 'xml':
                $thumbnail = $OUTPUT->pix_url('f/html-32')->out(false);
                break;

            default:
                $thumbnail = $OUTPUT->pix_url('f/unknown-32')->out(false);
                break;

        endswitch;
        return $thumbnail;
    }

    public function upload($saveas_filename, $maxbytes) {
        global $SESSION, $CFG;

        require_once($CFG->dirroot . '/repository/sword_upload/sword1/packager_mets_swap.php');
        require_once($CFG->dirroot . '/repository/sword_upload/sword1/utils.php');

        $license = trim(optional_param('license','',PARAM_RAW));
        $author = trim(optional_param('author','',PARAM_RAW));

        if (empty($saveas_filename)) {
            $filename = $_FILES['repo_upload_file']['name'];
	    $filename = str_replace(' ','',$filename);
	    $filename = RetirarAcentos($filename);
        } else {
            $parts =explode(".", $_FILES['repo_upload_file']['name']);
            $extension = end($parts);
            $extension = strtolower($extension);
            $filename = $saveas_filename.'.'.$extension;
	    $filename = str_replace(' ','',$filename);
	    $filename = RetirarAcentos($filename);

        }

        move_uploaded_file($_FILES['repo_upload_file']['tmp_name'],$CFG->dirroot . '/repository/sword_upload/temp/files/'.$filename);
        $pathinfo = pathinfo($CFG->dirroot . '/repository/sword_upload/temp/files/'.$filename);
        $extensao = '.'.$pathinfo['extension'];
        $mime_type = get_mimetype($extensao);

        $authors = explode(';',$author);

        $SESSION->entry['license-name'] = getLicenseTerm($license,'name-repositorio');
        $SESSION->entry['license-uri'] = getLicenseTerm($license,'uri');
        $SESSION->entry['author'] = $authors;

        $swordPackager = new PackagerMetsSwap($CFG->dirroot . '/repository/sword_upload/temp','files',$CFG->dirroot . '/repository/sword_upload/temp','mets_swap_package.zip');

        $swordPackager->setTitle($SESSION->entry['title']);
        $swordPackager->setAbstract($SESSION->entry['abstract']);
        $swordPackager->setDescription($SESSION->entry['description']);
        $swordPackager->setLanguage($SESSION->entry['language']);
        if (!empty($SESSION->entry['license-uri'])) {
            $swordPackager->addRights($SESSION->entry['license-name']);
            //$swordPackager->setRightsUri($SESSION->entry['license-uri']);
        }

        foreach ($SESSION->entry['author'] as $author) {
            $swordPackager->addCreator($author);
        }

        foreach ($SESSION->entry['type'] as $type) {
            $swordPackager->addTypes($type);
        }
		
		foreach ($SESSION->entry['subject'] as $subject) {
            $swordPackager->addSubject($subject);
        }

        $swordPackager->addFile($filename,$mime_type);

        $swordPackager->create();

        $testdr = $this->swordAppClient->deposit(
            $SESSION->entry['collection'],
            $SESSION->username,
            $SESSION->password,
            $SESSION->username,
            $CFG->dirroot . '/repository/sword_upload/temp/mets_swap_package.zip',
            'http://purl.org/net/sword-types/METSDSpaceSIP',
            'application/zip'
        );


        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            copy($CFG->dirroot . '/repository/sword_upload/temp/files/mets.xml',$CFG->dirroot . '/repository/sword_upload/temp/files/mets-bak.xml');
            @unlink($CFG->dirroot . '/repository/sword_upload/temp/files/mets.xml');
            @unlink($CFG->dirroot . '/repository/sword_upload/temp/files/'.$filename);
            @unlink($CFG->dirroot . '/repository/sword_upload/temp/mets_swap_package.zip');

            $SESSION->etapa = 'finish';

            return array(
                'url' => $testdr->sac_links[0],
            );

        } else {
            throw new moodle_exception('upload_error','sword_upload_repositpry');
        }

    }

    /**
     * Enable mulit-instance
     *
     * @return array
     */
    public static function get_instance_option_names() {
        return array('sword_url');
    }

    /**
     * define a configuration form
     *
     * @return bool
     */
    public static function instance_config_form($mform) {
        $mform->addElement('text', 'sword_url', get_string('sword_url', 'repository_sword_upload'));
        $mform->addElement('static', 'sword_url_intro', '', get_string('swordurltext', 'repository_sword_upload'));
        $mform->addRule('sword_url', get_string('required'), 'required', null, 'client');
        return true;
    }

    /**
     * Support external link only
     *
     * @return int
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL;
    }
    public function supported_filetypes() {
        return array('link', 'recurse');
    }

}

