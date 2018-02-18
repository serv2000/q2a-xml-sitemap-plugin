<?php
/*
 * Developed by Abdullah shalaan
 * abdullah.shalaan@gmail.com  
 * 
 * forked from the origin repo
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/xml-sitemap/qa-xml-sitemap.php
	Description: Page module class for XML sitemap plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_xml_sitemap
{
    private function check_sitemap_dir(){
        
                if(strlen($sitemap_dir)==0)
                                 qa_opt('xml_sitemap_directory',"sitemaps");

        $sitemap_dir=qa_opt('xml_sitemap_directory');
   
                $directory=QA_BASE_DIR.$sitemap_dir;
                $sitemap_dir_errors=[];
                if(!is_dir($directory)){
                    //try to create the directory
                    if(!mkdir($directory, 0777))
                    $sitemap_dir_errors[]= $directory. ' is not direcory';
                    
                }
              if(!is_writable($directory)){
                    $sitemap_dir_errors[]= $directory. ' is not writable';
                    
                }
                
        
                return $sitemap_dir_errors;
    }

    public function option_default($option)
	{
		switch ($option) {
			case 'xml_sitemap_show_questions':
			case 'xml_sitemap_show_users':
			case 'xml_sitemap_show_tag_qs':
			case 'xml_sitemap_show_category_qs':
			case 'xml_sitemap_show_categories':
				return true;
		}
	}


	public function admin_form(){
		require_once QA_INCLUDE_DIR . 'util/sort.php';

		$saved = false;

		if (qa_clicked('xml_sitemap_save_button')) {
			qa_opt('xml_sitemap_show_questions', (int)qa_post_text('xml_sitemap_show_questions_field'));
			qa_opt('xml_sitemap_directory', qa_post_text('xml_sitemap_directory'));

			if (!QA_FINAL_EXTERNAL_USERS)
				qa_opt('xml_sitemap_show_users', (int)qa_post_text('xml_sitemap_show_users_field'));

			if (qa_using_tags())
				qa_opt('xml_sitemap_show_tag_qs', (int)qa_post_text('xml_sitemap_show_tag_qs_field'));

			if (qa_using_categories()) {
				qa_opt('xml_sitemap_show_category_qs', (int)qa_post_text('xml_sitemap_show_category_qs_field'));
				qa_opt('xml_sitemap_show_categories', (int)qa_post_text('xml_sitemap_show_categories_field'));
			}

			$saved = true;
		}

                //
                //sitemap dir errors check
$sitemap_dir_errors= $this->check_sitemap_dir();
          
               // exit; 
                //
                ///
		$form = array(
			'ok' => $saved ? 'XML sitemap settings saved' : null,

			'fields' => array(
		array(
					'label' => 'sitemap directory:',
					'value' => qa_html(qa_opt('xml_sitemap_directory')),
					'tags' => 'name="xml_sitemap_directory"',
					'error' => (count($sitemap_dir_errors)==0) ? null : implode('<br>', $sitemap_dir_errors),
				),	
                            'questions' => array(
					'label' => 'Include question pages',
					'type' => 'checkbox',
					'value' => (int)qa_opt('xml_sitemap_show_questions'),
					'tags' => 'name="xml_sitemap_show_questions_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="xml_sitemap_save_button"',
				),
			),
		);

		if (!QA_FINAL_EXTERNAL_USERS) {
			$form['fields']['users'] = array(
				'label' => 'Include user pages',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_users'),
				'tags' => 'name="xml_sitemap_show_users_field"',
			);
		}

		if (qa_using_tags()) {
			$form['fields']['tagqs'] = array(
				'label' => 'Include question list for each tag',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_tag_qs'),
				'tags' => 'name="xml_sitemap_show_tag_qs_field"',
			);
		}

		if (qa_using_categories()) {
			$form['fields']['categoryqs'] = array(
				'label' => 'Include question list for each category',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_category_qs'),
				'tags' => 'name="xml_sitemap_show_category_qs_field"',
			);

			$form['fields']['categories'] = array(
				'label' => 'Include category browser',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_categories'),
				'tags' => 'name="xml_sitemap_show_categories_field"',
			);
		}

		return $form;
	}


	public function suggest_requests()
	{
		return array(
			array(
				'title' => 'XML Sitemap',
				'request' => 'sitemap.xml',
				'nav' => null, // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}


	public function match_request($request)
	{
		return ($request == 'sitemap.xml');
	}


	public function process_request($request)
	{
            $dirs_error=$this->check_sitemap_dir();
            if(count($dirs_error)>0){
                echo implode("<br> ", $dirs_error);
                exit;
            }
		@ini_set('display_errors', FALSE); // we don't want to show PHP errors inside XML
            $SitemapsDIr=qa_opt('xml_sitemap_directory');
            $siteMapFullDir=QA_BASE_DIR.$SitemapsDIr;
            $sitemaplist=[]    ;
            $sitemapHead='<?xml version="1.0" encoding="UTF-8"?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">';
            $sitemapFoot='</urlset>';

		// Question Maps

		if (qa_opt('xml_sitemap_show_questions')) {
			$hotstats = qa_db_read_one_assoc(qa_db_query_sub(
				"SELECT MIN(hotness) AS base, MAX(hotness)-MIN(hotness) AS spread FROM ^posts WHERE type='Q'"
			));
                  
			$nextpostid = 0;
                        $questionSitemapNumber=1;
			while (1) {
				$questions = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT postid, title, hotness FROM ^posts WHERE postid>=# AND type='Q' ORDER BY postid LIMIT 1",
					$nextpostid
				));
                               $mamString=null;

				if (!count($questions))
					break;
                              //  $mamString=null;
                                
				foreach ($questions as $question) {
				$mamString.=$this->sitemap_output(qa_q_request($question['postid'], $question['title']),
						0.1 + 0.9 * ($question['hotness'] - $hotstats['base']) / (1 + $hotstats['spread']));
                                        
					$nextpostid = max($nextpostid, $question['postid'] + 1);
				}
                                $mapurl=$SitemapsDIr.'/questions-'.$questionSitemapNumber.'.xml';
                                
                                $sitemaplist[]=$mapurl;
                                
                                file_put_contents($mapurl, $sitemapHead.$mamString.$sitemapFoot);
                                                        $questionSitemapNumber++;

                                
      
			}

		}


		// User pages

		if (!QA_FINAL_EXTERNAL_USERS && qa_opt('xml_sitemap_show_users')) {
			$nextuserid = 0;
                        $usersSitemapNumber=1;
			while (1) {
            $mamString=null;

				$users = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT userid, handle FROM ^users WHERE userid>=# ORDER BY userid LIMIT 100",
					$nextuserid
				));

				if (!count($users))
					break;

				foreach ($users as $user) {
				$mamString.=$this->sitemap_output('user/' . $user['handle'], 0.25);
					$nextuserid = max($nextuserid, $user['userid'] + 1);
				}
                                                           $mapurl=$SitemapsDIr.'/users-'.$usersSitemapNumber.'.xml';
                                
                                $sitemaplist[]=$mapurl;
                                
                                file_put_contents($mapurl, $sitemapHead.$mamString.$sitemapFoot);
                                                        $usersSitemapNumber++;

                                
                                
                                
			}
		}

		// Tag pages

		if (qa_using_tags() && qa_opt('xml_sitemap_show_tag_qs')) {
			$nextwordid = 0;
                $tagSitemapNumber=1;
			while (1) {
				$tagwords = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT wordid, word, tagcount FROM ^words WHERE wordid>=# AND tagcount>0 ORDER BY wordid LIMIT 100",
					$nextwordid
				));
$mamString=null;
				if (!count($tagwords))
					break;

				foreach ($tagwords as $tagword) {
					$mamString.=$this->sitemap_output('tag/' . $tagword['word'], 0.5 / (1 + (1 / $tagword['tagcount']))); // priority between 0.25 and 0.5 depending on tag frequency
					$nextwordid = max($nextwordid, $tagword['wordid'] + 1);
				}
                                                                                           $mapurl=$SitemapsDIr.'/tags-'.$tagSitemapNumber.'.xml';

                                              $sitemaplist[]=$mapurl;
                                
                                file_put_contents($mapurl, $sitemapHead.$mamString.$sitemapFoot);
                                                        $tagSitemapNumber++;

			}
		}


		// Question list for each category

		if (qa_using_categories() && qa_opt('xml_sitemap_show_category_qs')) {
			$nextcategoryid = 0;
$QuestionListforeachCategorysSitemapNumber=1;
			while (1) {
				$categories = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT categoryid, backpath FROM ^categories WHERE categoryid>=# AND qcount>0 ORDER BY categoryid LIMIT 2",
					$nextcategoryid
				));
                                
$mamString=null;
				if (!count($categories))
					break;

				foreach ($categories as $category) {
					$mamString.=$this->sitemap_output('questions/' . implode('/', array_reverse(explode('/', $category['backpath']))), 0.5);
					$nextcategoryid = max($nextcategoryid, $category['categoryid'] + 1);
				}
                                                                                                             $mapurl=$SitemapsDIr.'/QuestionListforeachCategorys-'.$QuestionListforeachCategorysSitemapNumber.'.xml';

                                              $sitemaplist[]=$mapurl;
                                
                                file_put_contents($mapurl, $sitemapHead.$mamString.$sitemapFoot);
                                                        $QuestionListforeachCategorysSitemapNumber++;

			}
		}


		// Pages in category browser

		if (qa_using_categories() && qa_opt('xml_sitemap_show_categories')) {
			$this->sitemap_output('categories', 0.5);

			$nextcategoryid = 0;
$categoriesPagesSitemapNumber=1;
			while (1) { // only find categories with a child
				$categories = qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT parent.categoryid, parent.backpath FROM ^categories AS parent " .
					"JOIN ^categories AS child ON child.parentid=parent.categoryid WHERE parent.categoryid>=# GROUP BY parent.categoryid LIMIT 100",
					$nextcategoryid
				));
$mamString=null;
				if (!count($categories))
					break;

				foreach ($categories as $category) {
					$mamString.=$this->sitemap_output('categories/' . implode('/', array_reverse(explode('/', $category['backpath']))), 0.5);
					$nextcategoryid = max($nextcategoryid, $category['categoryid'] + 1);
				}
 $mapurl=$SitemapsDIr.'/categoriesPages-'.$categoriesPagesSitemapNumber.'.xml';

                                              $sitemaplist[]=$mapurl;
                                
                                file_put_contents($mapurl, $sitemapHead.$mamString.$sitemapFoot);
                                                        $categoriesPagesSitemapNumber++;
                                
			}
		}
      
                
                $sitemapListString='<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">';
             
        foreach ($sitemaplist as $map) {
                            $sitemapListString.= "
        <sitemap>
                <loc>".qa_opt('site_url')."/".$map."</loc>
                <lastmod>" . date('Y-m-d\TH:i:s+00:00') ."</lastmod>
        </sitemap>
        ";

                }
                        $sitemapListString.="</sitemapindex>";
                  $sitemapindex=$SitemapsDIr.'/mapindexs.xml';
                  file_put_contents($sitemapindex, $sitemapListString);
                        header( "Content-Type: application/xml" );
                  
                        echo $sitemapListString;
                        //print_r($sitemaplist);

		return null;
	}


	/**
	 * @deprecated This function will become private in Q2A 1.8. It is specific to this plugin and
	 * should not be used by outside code.
	 */
	public function sitemap_output($request, $priority)
	{
		return "\t<url>\n" .
			"\t\t<loc>" . qa_xml(qa_path($request, null, qa_opt('site_url'))) . "</loc>\n" .
			"\t\t<priority>" . max(0, min(1.0, $priority)) . "</priority>\n" .
			"\t</url>\n";
	}
}
