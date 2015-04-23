<?php

class newsitemParseTest {

	public static function runAllTests() {
		$successful = 0;
		
		if(self::newsitemParseTestIntireArrayOk()) $successful++;
		if(self::pubStatusPublish()) $successful++;
		if(self::contentMissingTest()) $successful++;
		if(self::headlineMissingTest()) $successful++;
		if(self::guidMissingTest()) $successful++;
		if(self::versionMissingTest()) $successful++;
		if(self::contentAnternate1Test()) $successful++;
		if(self::contentAnternate2Test()) $successful++;
		if(self::contentAnternate3Test()) $successful++;
		if(self::headlineAlternate1Test()) $successful++;
		if(self::headlineAlternate2Test()) $successful++;
		
		return $successful;
	}
	
	private static function newsitemParseTestIntireArrayOk() {
		//Arrange
		echo "<h4>Running newsitemParseTestIntireArrayOk</h4>";
		//require("newsitemParse.php");
		
		$xmlComplete = '<newsMessage xmlns="http://iptc.org/std/nar/2006-10-01/">
							<header>
								<sent>2015-03-18T12:00:00.000+01:00</sent>
							</header>
								<itemSet>
									<packageItem>
										<groupSet root="root">
											<group id="group-20551954" role="vggr:text-mainarticle">
												<itemRef version="4" residref="article-text">
													<itemClass qcode="ninat:text"/>
												</itemRef>
												<itemRef version="4" residref="article-image">
													<itemClass qcode="ninat:picture"/>
												</itemRef>
											</group>
										</groupSet>
									</packageItem>
									<newsItem version="1" guid="article-text">
										<rightsInfo>
											<copyrightHolder>
												<name>Copyright Holder</name>
											</copyrightHolder>
											<copyrightNotice>Copyright Notice</copyrightNotice>
										</rightsInfo>
										<itemMeta>
											<embargoed>2015-03-18T14:00:00.000+01:00</embargoed>
											<versionCreated>2015-03-18T12:00:00.000+01:00</versionCreated>
											<firstCreated>2015-03-18T10:00:00.000+01:00</firstCreated>
											<pubStatus qcode="stat:usable"/>
										</itemMeta>
										<contentMeta>
											<creator qcode="creator:qcode" role="creator:role" uri="creator:uri">
												<name>Creator</name>
													<personDetails>
														<contactInfo>
															<email>creator@email.com</email>
														</contactInfo>
													</personDetails>
											</creator>
											<contributor role="contributor:role" qcode="contributor:qcode" uri="contributor:uri" literal="Contributor">
													<personDetails>
														<contactInfo>
															<email>contributor@email.com</email>
														</contactInfo>
													</personDetails>
											</contributor>
											<subject qcode="subject:qcode" uri="subject:uri" type="subject:type">
												<name xml:lang="subject:lang" role="subject:role" >Subject</name>
												<sameAs qcode="sameAs:qcode" uri="sameAs:uri" type="sameAs:type">
													<name xml:lang="sameAs:lang" role="sameAs:role" >SameAs</name>
												</sameAs>
												<broader qcode="broader:qcode" uri="broader:uri" type="broader:type">
													<name xml:lang="broader:lang" role="broader:role" >Broader</name>
												</broader>
											</subject>
											<keyword>Keyword 1</keyword>
											<keyword>Keyword 2</keyword>
											<keyword>Keyword 3</keyword>
											<headline>Headline</headline>
											<language tag="no"/>
											<slugline>Slugline</slugline>
										</contentMeta>
										<contentSet>
											<inlineXML contenttype="application/xhtml+xml">
												<html xmlns="http://www.w3.org/1999/xhtml">
													<body>
														<article>
															<div itemprop="articleBody"><p>Article content</p></div>
														</article>
													</body>
												</html>
											</inlineXML>
										</contentSet>
										</newsItem>
										<newsItem guid="article-image">
											<contentMeta>
												<description>Image description</description>
											</contentMeta>
											<contentSet>
												<remoteContent
													href="http://imageHref"
													size="12345"
													width="123"
													height="12"
													contenttype="image/jpeg"
													colourspace="colsp:AdobeRGB"
													rendition="rnd:highRes">
												</remoteContent>														
											</contentSet>
										</newsItem>
									</itemSet>
								</newsMessage>';
		
		$status_codeCorrect = 200;
		$post_contentCorrect = "<p>Article content</p>";
		$post_nameCorrect = "Slugline";
		$post_titleCorrect = "Headline";
		$post_statusCorrect = "future";
		$tags_inputCorrect = "Keyword 1,Keyword 2,Keyword 3,";
		$nml2_guidCorrect = "article-text";
		$nml2_versionCorrecy = "1";
		$nml2_firstCreatedCorrect = "2015-03-18T10:00:00.000+01:00";
		$nml2_versionCreatedCorrect = "2015-03-18T12:00:00.000+01:00";
		$nml2_embarogDateCorrect = "2015-03-18T14:00:00.000+01:00";
		$nml2_newsMessageSendtCorrect = "2015-03-18T12:00:00.000+01:00";
		$nml2_languageCorrect = "no";
		$nml2_copyrightHolderCorrect = "Copyright Holder";
		$nml2_copyrightNoticeCorrect = "Copyright Notice";
		$user_loginCreatorCorrect = "Creator";
		$descriptionCreatorCorrect = "creator:role";
		$user_emailCreatorCorrect  = "creator@email.com";
		$nml2_qcodeCreatorCorrect = "creator:qcode";
		$nml2_uriCreatorCorrect = "creator:uri";
		$user_loginContributorCorrect = "Contributor";
		$descriptionContributorCorrect = "contributor:role";
		$user_emailContributorCorrect  = "contributor@email.com";
		$nml2_qcodeContributorCorrect = "contributor:qcode";
		$nml2_uriContributorCorrect = "contributor:uri";
		$qcodeSubjectCorrect = "subject:qcode";
		$textSubjectCorrect = "Subject";
		$langSubjectCorrect = "subject:lang";
		$roleSubjectCorrect = "subject:role";
		$typeSubjectCorrect = "subject:type";
		$uriSubjectCorrect = "subject:uri";
		$qcodeSameAsCorrect = "sameAs:qcode";
		$textSameAsCorrect = "SameAs";
		$langSameAsCorrect = "sameAs:lang";
		$roleSameAsCorrect = "sameAs:role";
		$typeSameAsCorrect = "sameAs:type";
		$uriSameAsCorrect = "sameAs:uri";
		$qcodeBroaderCorrect = "broader:qcode";
		$textBroaderCorrect = "Broader";
		$langBroaderCorrect = "broader:lang";
		$roleBroaderCorrect = "broader:role";
		$typeBroaderCorrect = "broader:type";
		$uriBroaderCorrect = "broader:uri";
		$hrefCorrect = "http://imageHref";
		$sizeCorrect = "12345";
		$widthCorrect = "123";
		$heightCorrect = "12";
		$contenttypeCorrect = "image/jpeg";
		$colourspaceCorrect = "colsp:AdobeRGB";
		$renditionCorrect = "rnd:highRes";
		$imagedescriptionCorrect = "Image description";
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xmlComplete);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		
		
		//Assert
		$success = true;
		
		if($parseArray['status_code'] != $status_codeCorrect) {
			echo "Test one failed: " . $parseArray['status_code'] . " != " . $status_codeCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['post']['post_content'] != $post_contentCorrect) {
			echo "Test two failed: " . $parseArray[0]['post']['post_content'] . " != " . $post_contentCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['post']['post_name'] != $post_nameCorrect) {
			echo "Test three failed: " . $parseArray[0]['post']['post_name'] . " != " . $post_nameCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['post']['post_title'] != $post_titleCorrect) {
			echo "Test four failed: " . $parseArray[0]['post']['post_title'] . " != " . $post_titleCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['post']['post_status'] != $post_statusCorrect) {
			echo "Test five failed: " . $parseArray[0]['post']['post_status'] . " != " . $post_statusCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['post']['tags_input'] != $tags_inputCorrect) {
			echo "Test six failed: " . $parseArray[0]['post']['tags_input'] . " != " . $tags_inputCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_guid'] != $nml2_guidCorrect) {
			echo "Test seven failed: " . $parseArray[0]['meta']['nml2_guid'] . " != " . $nml2_guidCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_version'] != $nml2_versionCorrecy) {
			echo "Test eight failed: " . $parseArray[0]['meta']['nml2_version'] . " != " . $nml2_versionCorrecy . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_firstCreated'] != $nml2_firstCreatedCorrect) {
			echo "Test nine failed: " . $parseArray[0]['meta']['nml2_firstCreated'] . " != " . $nml2_firstCreatedCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_versionCreated'] != $nml2_versionCreatedCorrect) {
			echo "Test ten failed: " . $parseArray[0]['meta']['nml2_versionCreated'] . " != " . $nml2_versionCreatedCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_embarogDate'] != $nml2_embarogDateCorrect) {
			echo "Test eleven failed: " . $parseArray[0]['meta']['nml2_embarogDate'] . " != " . $nml2_embarogDateCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_newsMessageSendt'] != $nml2_newsMessageSendtCorrect) {
			echo "Test twelve failed: " . $parseArray[0]['meta']['nml2_newsMessageSendt'] . " != " . $nml2_newsMessageSendtCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_language'] != $nml2_languageCorrect) {
			echo "Test thirteen failed: " . $parseArray[0]['meta']['nml2_language'] . " != " . $nml2_languageCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_copyrightHolder'] != $nml2_copyrightHolderCorrect) {
			echo "Test fourteen failed: " . $parseArray[0]['meta']['nml2_copyrightHolder'] . " != " . $nml2_copyrightHolderCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['meta']['nml2_copyrightNotice'] != $nml2_copyrightNoticeCorrect) {
			echo "Test fifteen failed: " . $parseArray[0]['meta']['nml2_copyrightNotice'] . " != " . $nml2_copyrightNoticeCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][0]['user_login'] != $user_loginCreatorCorrect) {
			echo "Test sixteen failed: " . $parseArray[0]['users'][0]['user_login'] . " != " . $user_loginCreatorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][0]['description'] != $descriptionCreatorCorrect) {
			echo "Test seventeen failed: " . $parseArray[0]['users'][0]['description'] . " != " . $descriptionCreatorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][0]['user_email'] != $user_emailCreatorCorrect) {
			echo "Test eighteen failed: " . $parseArray[0]['users'][0]['user_email'] . " != " . $user_emailCreatorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][0]['nml2_qcode'] != $nml2_qcodeCreatorCorrect) {
			echo "Test nineteen failed: " . $parseArray[0]['users'][0]['nml2_qcode'] . " != " . $nml2_qcodeCreatorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][0]['nml2_uri'] != $nml2_uriCreatorCorrect) {
			echo "Test twenty failed: " . $parseArray[0]['users'][0]['nml2_uri'] . " != " . $nml2_uriCreatorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][1]['user_login'] != $user_loginContributorCorrect) {
			echo "Test twenty-one failed: " . $parseArray[0]['users'][1]['user_login'] . " != " . $user_loginContributorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][1]['description'] != $descriptionContributorCorrect) {
			echo "Test twenty-two failed: " . $parseArray[0]['users'][1]['description'] . " != " . $descriptionContributorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][1]['user_email'] != $user_emailContributorCorrect) {
			echo "Test twenty-three failed: " . $parseArray[0]['users'][1]['user_email'] . " != " . $user_emailContributorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][1]['nml2_qcode'] != $nml2_qcodeContributorCorrect) {
			echo "Test twenty-four failed: " . $parseArray[0]['users'][1]['nml2_qcode'] . " != " . $nml2_qcodeContributorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['users'][1]['nml2_uri'] != $nml2_uriContributorCorrect) {
			echo "Test twenty-five failed: " . $parseArray[0]['users'][1]['nml2_uri'] . " != " . $nml2_uriContributorCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['qcode'] != $qcodeSubjectCorrect) {
			echo "Test twenty-six failed: " . $parseArray[0]['subjects'][0]['qcode'] . " != " . $qcodeSubjectCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['name'][0]['text'] != $textSubjectCorrect) {
			echo "Test twenty-seven failed: " . $parseArray[0]['subjects'][0]['name'][0]['text'] . " != " . $textSubjectCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['name'][0]['lang'] != $langSubjectCorrect) {
			echo "Test twenty-eight failed: " . $parseArray[0]['subjects'][0]['name'][0]['lang'] . " != " . $langSubjectCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['name'][0]['role'] != $roleSubjectCorrect) {
			echo "Test twenty-nine failed: " . $parseArray[0]['subjects'][0]['name'][0]['role'] . " != " . $roleSubjectCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['type'] != $typeSubjectCorrect) {
			echo "Test thirty failed: " . $parseArray[0]['subjects'][0]['type'] . " != " . $typeSubjectCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['uri'] != $uriSubjectCorrect) {
			echo "Test thirty-one failed: " . $parseArray[0]['subjects'][0]['uri'] . " != " . $uriSubjectCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['sameAs'][0]['qcode'] != $qcodeSameAsCorrect) {
			echo "Test thirty-two failed: " . $parseArray[0]['subjects'][0]['SameAs'][0]['qcode'] . " != " . $qcodeSameAsCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['sameAs'][0]['name'][0]['text'] != $textSameAsCorrect) {
			echo "Test thirty-three failed: " . $parseArray[0]['subjects'][0]['sameAs'][0]['name'][0]['text'] . " != " . $textSameAsCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['sameAs'][0]['name'][0]['lang'] != $langSameAsCorrect) {
			echo "Test thirty-four failed: " . $parseArray[0]['subject'][0]['sameAs'][0]['name'][0]['lang'] . " != " . $langSameAsCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['sameAs'][0]['name'][0]['role'] != $roleSameAsCorrect) {
			echo "Test thirty-five failed: " . $parseArray[0]['subject'][0]['sameAs'][0]['name'][0]['role'] . " != " . $roleSameAsCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['sameAs'][0]['type'] != $typeSameAsCorrect) {
			echo "Test thirty-six failed: " . $parseArray[0]['subjects'][0]['sameAs'][0]['type'] . " != " . $typeSameAsCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['sameAs'][0]['uri'] != $uriSameAsCorrect) {
			echo "Test thirty-seven failed: " . $parseArray[0]['subjects'][0]['sameAs'][0]['uri'] . " != " . $uriSameAsCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['broader'][0]['qcode'] != $qcodeBroaderCorrect) {
			echo "Test thirty-eight failed: " . $parseArray[0]['subjects'][0]['broader'][0]['qcode'] . " != " . $qcodeBroaderCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['broader'][0]['name'][0]['text'] != $textBroaderCorrect) {
			echo "Test thirty-nine failed: " . $parseArray[0]['subjects'][0]['broader'][0]['name'][0]['text'] . " != " . $textBroaderCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['broader'][0]['name'][0]['lang'] != $langBroaderCorrect) {
			echo "Test forty failed: " . $parseArray[0]['subjects'][0]['broader'][0]['name'][0]['lang'] . " != " . $langBroaderCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['broader'][0]['name'][0]['role'] != $roleBroaderCorrect) {
			echo "Test forty-one failed: " . $parseArray[0]['subjects'][0]['broader'][0]['name'][0]['role'] . " != " . $roleBroaderCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['broader'][0]['type'] != $typeBroaderCorrect) {
			echo "Test forty-two failed: " . $parseArray[0]['subjects'][0]['broader'][0]['type'] . " != " . $typeBroaderCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['subjects'][0]['broader'][0]['uri'] != $uriBroaderCorrect) {
			echo "Test forty-three failed: " . $parseArray[0]['subjects'][0]['broader'][0]['uri'] . " != " . $uriBroaderCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['photo'][0]['href'] != $hrefCorrect) {
			echo "Test forty-four failed: " . $parseArray[0]['photo'][0]['href'] . " != " . $hrefCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['photo'][0]['size'] != $sizeCorrect) {
			echo "Test forty-five failed: " . $parseArray[0]['photo'][0]['size'] . " != " . $sizeCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['photo'][0]['width'] != $widthCorrect) {
			echo "Test forty-six failed: " . $parseArray[0]['photo'][0]['width'] . " != " . $widthCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['photo'][0]['height'] != $heightCorrect) {
			echo "Test forty-seven failed: " . $parseArray[0]['photo'][0]['height'] . " != " . $heightCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['photo'][0]['contenttype'] != $contenttypeCorrect) {
			echo "Test forty-eight failed: " . $parseArray[0]['photo'][0]['contenttype'] . " != " .  $contenttypeCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['photo'][0]['colourspace'] != $colourspaceCorrect) {
			echo "Test forty-nine failed: " . $parseArray[0]['photo'][0]['colourspace'] . " != " . $colourspaceCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['photo'][0]['rendition'] != $renditionCorrect) {
			echo "Test fifty failed: " . $parseArray[0]['photo'][0]['rendition'] . " != " . $renditionCorrect . "<br/>";
			$success = false;
		}
		
		if($parseArray[0]['photo'][0]['description'] != $imagedescriptionCorrect) {
			echo "Test fifty-one failed: " . $parseArray[0]['photo'][0]['description'] . " != " . $imagedescriptionCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
	
	private static function pubStatusPublish() {
		//Arrange
		echo "<h4>Running pubStatusPublish</h4>";
		
		$xml = '<newsItem guid="guid" version="1">
						
					</newsItem>';
		
		$pubStatusCorrect = 'publish';
		
		//Act
		try{
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		//Assert
		$success = true;
		
		if($parseArray[0]['post']['post_status'] != $pubStatusCorrect) {
			echo "Test one failed: " .$parseArray[0]['post']['post_status'] . " != " . $pubStatusCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
	
	private static function contentMissingTest() {
		//Arrange
		echo "<h4>Running contentMissingTest</h4>";
		
		$xml = '<newsItem>
				</newsItem>';
				
		$status_codeCorrect = 400;
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		
		//Assert
		$success = true;
		
		if($parseArray['status_code'] != $status_codeCorrect) {
			echo "Test one failed: " . $parseArray['status_code'] . " != " . $status_codeCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
	
	private static function headlineMissingTest() {
		//Arrange
		echo "<h4>Running headlineMissingTest</h4>";
		
		$xml = '<newsItem>
					<contentSet>
						<inlineXML contenttype="application/xhtml+xml">
							<html xmlns="http://www.w3.org/1999/xhtml">
								<body>
									<article>
										<div itemprop="articleBody"><p>Article content</p></div>
									</article>
								</body>
							</html>
						</inlineXML>
					</contentSet>
				</newsItem>';
		
		$status_codeCorrect = 400;
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		//Assert
		$success = true;
		
		if($parseArray['status_code'] != $status_codeCorrect) {
			echo "Test one failed: " . $parseArray['status_code'] . " != " . $status_codeCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
	
	private static function guidMissingTest() {
		//Arrange
		echo "<h4>Running guidMissingTest</h4>";
		
		$xml = '<newsItem>
					<contentMeta>
						<headline>
							Headline
						</headline>
					</contentMeta>
					<contentSet>
						<inlineXML contenttype="application/xhtml+xml">
							<html xmlns="http://www.w3.org/1999/xhtml">
								<body>
									<article>
										<div itemprop="articleBody"><p>Article content</p></div>
									</article>
								</body>
							</html>
						</inlineXML>
					</contentSet>
				</newsItem>';
		
		$status_codeCorrect = 400;
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		
		//Assert
		$success = true;
		
		var_dump($success);
		return $success;
	}
	
	private static function versionMissingTest() {
		//Arrange
		echo "<h4>Running versiondMissingTest</h4>";
		
		$xml = '<newsItem guid="guid">
					<contentMeta>
						<headline>
							Headline
						</headline>
					</contentMeta>
					<contentSet>
						<inlineXML contenttype="application/xhtml+xml">
							<html xmlns="http://www.w3.org/1999/xhtml">
								<body>
									<article>
										<div itemprop="articleBody"><p>Article content</p></div>
									</article>
								</body>
							</html>
						</inlineXML>
					</contentSet>
				</newsItem>';
		
		$status_codeCorrect = 400;
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		
		//Assert
		$success = true;
		
		if($parseArray['status_code'] != $status_codeCorrect) {
			echo "Test one failed: " . $parseArray['status_code'] . " != " . $status_codeCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
	
	private static function contentAnternate1Test() {
		//Arrange
		echo "<h4>Running contentAnternate1Test</h4>";
		
		$xml = '<newsItem>
					<contentSet>
						<inlineXML>
							<html xmlns="http://www.w3.org/1999/xhtml">
								<body>Content</body>
							</html>
						</inlineXML>
					</contentSet>
				</newsItem>';
		
		$post_contentCorrect = "Content";
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		
		//Assert
		$success = true;
		
		if($parseArray[0]['post']['post_content'] != $post_contentCorrect) {
			echo "Test one failed: " . $parseArray[0]['post']['post_content'] . " != " . $post_contentCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
		
	}
	
	private static function contentAnternate2Test() {
		//Arrange
		echo "<h4>Running contentAnternate2Test</h4>";
		
		$xml = '<newsItem>
					<contentSet>
						<inlineXML>
							<nitf xmlns="http://iptc.org/std/NITF/2006-10-18/">
								<body>
									<body.content>Content</body.content>
								</body>
							</nitf>
						</inlineXML>
					</contentSet>
				</newsItem>';
				
		$post_contentCorrect = "Content";
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		//Assert
		$success = true;
		
		if($parseArray[0]['post']['post_content'] != $post_contentCorrect) {
			echo "Test one failed: " . $parseArray[0]['post']['post_content'] . " != " . $post_contentCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
	
	private static function contentAnternate3Test() {
		//Arrange
		echo "<h4>Running contentAnternate3Test</h4>";
		
		$xml = '<newsItem>
					<contentSet>
						<inlineData>Content</inlineData>
					</contentSet>
				</newsItem>';
				
		$post_contentCorrect = "Content";
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		//Assert
		$success = true;
		
		if($parseArray[0]['post']['post_content'] != $post_contentCorrect) {
			echo "Test one failed: " . $parseArray[0]['post']['post_content'] . " != " . $post_contentCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
	
	private static function headlineAlternate1Test() {
		//Arrange
		echo "<h4>Running headlineAlternate1Test</h4>";
		
		$xml = '<newsItem>
					<contentSet>
						<inlineXML>
							<html xmlns="http://www.w3.org/1999/xhtml">
								<head>
									<title>Headline</title>
								</head>
							</html>
						</inlineXML>
					</contentSet>
				</newsItem>';
		
		$post_titleCorrect = "Headline";
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		//Assert
		$success = true;
		
		if($parseArray[0]['post']['post_title'] != $post_titleCorrect) {
			echo "Test one failed: " . $parseArray[0]['post']['post_title'] . " != " . $post_titleCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
	
	private static function headlineAlternate2Test() {
		//Arrange
		echo "<h4>Running headlineAlternate2Test</h4>";
		
		$xml = '<newsItem>
					<contentSet>
						<inlineXML>
							<html xmlns="http://www.w3.org/1999/xhtml">
								<head>
									<title>Headline</title>
								</head>
							</html>
						</inlineXML>
					</contentSet>
				</newsItem>';
		
		$post_titleCorrect = "Headline";
		
		//Act
		try {
			$parseArray = newsitemParse::createPost($xml);
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		//Assert
		$success = true;
		
		if($parseArray[0]['post']['post_title'] != $post_titleCorrect) {
			echo "Test one failed: " . $parseArray[0]['post']['post_title'] . " != " . $post_titleCorrect . "<br/>";
			$success = false;
		}
		
		var_dump($success);
		return $success;
	}
}

?>