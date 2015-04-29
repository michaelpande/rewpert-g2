<?php
require_once(__DIR__ . '/../parser/newsItemParse.php');

class newsitemParseTest extends PHPUnit_Framework_TestCase {


    private function completeNewsML() {
        return '<newsMessage xmlns="http://iptc.org/std/nar/2006-10-01/">
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
    }

    public function testStatusCodeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $status_codeCorrect = 200;

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $status_code = $parseArray['status_code'];

        //Assert
        $this->assertEquals($status_codeCorrect, $status_code);
    }

    public function testPostContentOK() {
        //Arrange
        $xml = self::completeNewsML();
        $post_contentCorrect = "<p>Article content</p>";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $post_content = $parseArray[0]['post']['post_content'];

        //Assert
        $this->assertEquals($post_contentCorrect, $post_content);
    }

    public function testPostNameOK() {
        //Arrange
        $xml = self::completeNewsML();
        $post_nameCorrect = "Slugline";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $post_meta = $parseArray[0]['post']['post_name'];

        //Assert
        $this->assertEquals($post_nameCorrect, $post_meta);
    }

    public function testPostTitleOK() {
        //Arrange
        $xml = self::completeNewsML();
        $post_titleCorrect = "Headline";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $post_title = $parseArray[0]['post']['post_title'];

        //Assert
        $this->assertEquals($post_titleCorrect, $post_title);
    }

    public function testPostStatusOK() {
        //Arrange
        $xml = self::completeNewsML();
        $post_statusCorrect = "future";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $post_status = $parseArray[0]['post']['post_status'];

        //Assert
        $this->assertEquals($post_statusCorrect, $post_status);
    }

    public function testPostTagsOK() {
        //Arrange
        $xml = self::completeNewsML();
        $tags_inputCorrect = "Keyword 1,Keyword 2,Keyword 3,";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $tags_input = $parseArray[0]['post']['tags_input'];

        //Assert
        $this->assertEquals($tags_inputCorrect, $tags_input);
    }

    public function testMetaGuidOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_guidCorrect = "article-text";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_guid = $parseArray[0]['meta']['nml2_guid'];

        //Assert
        $this->assertEquals($nml2_guidCorrect, $nml2_guid);
    }

    public function testMetaVersionOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_versionCorrect = "1";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_version = $parseArray[0]['meta']['nml2_version'];

        //Assert
        $this->assertEquals($nml2_versionCorrect, $nml2_version);
    }

    public function testMetaFirstCreatedOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_firstCreatedCorrect = "2015-03-18T10:00:00.000+01:00";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_firstCreated = $parseArray[0]['meta']['nml2_firstCreated'];

        //Assert
        $this->assertEquals($nml2_firstCreatedCorrect, $nml2_firstCreated);
    }

    public function testMetaVersionCreatedOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_versionCreatedCorrect = "2015-03-18T12:00:00.000+01:00";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_versionCreated = $parseArray[0]['meta']['nml2_versionCreated'];

        //Assert
        $this->assertEquals($nml2_versionCreatedCorrect, $nml2_versionCreated);
    }

    public function testMetaNewsMessageSentOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_newsMessageSentCorrect = "2015-03-18T12:00:00.000+01:00";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_newsMessage = $parseArray[0]['meta']['nml2_newsMessageSent'];

        //Assert
        $this->assertEquals($nml2_newsMessageSentCorrect, $nml2_newsMessage);
    }

    public function testMetaLanguageOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_languageCorrect = "no";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_language = $parseArray[0]['meta']['nml2_language'];

        //Assert
        $this->assertEquals($nml2_languageCorrect, $nml2_language);
    }

    public function testMetaCopyrightHolderOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_copyrightHolderCorrect = "Copyright Holder";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_copyrightHolder = $parseArray[0]['meta']['nml2_copyrightHolder'];

        //Assert
        $this->assertEquals($nml2_copyrightHolderCorrect, $nml2_copyrightHolder);
    }

    public function testMetaCopyrightNoticeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_copyrightNoticeCorrect = "Copyright Notice";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_copyrightNotice = $parseArray[0]['meta']['nml2_copyrightNotice'];

        //Assert
        $this->assertEquals($nml2_copyrightNoticeCorrect, $nml2_copyrightNotice);
    }

    public function testCreatorLoginOK() {
        //Arrange
        $xml = self::completeNewsML();
        $user_loginCreatorCorrect = "Creator";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $user_loginCreator = $parseArray[0]['users'][0]['user_login'];

        //Assert
        $this->assertEquals($user_loginCreatorCorrect, $user_loginCreator);
    }

    public function testCreatorDescriptionOK() {
        //Arrange
        $xml = self::completeNewsML();
        $descriptionCreatorCorrect = "creator:role";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $descriptionCreator = $parseArray[0]['users'][0]['description'];

        //Assert
        $this->assertEquals($descriptionCreatorCorrect, $descriptionCreator);
    }

    public function testCreatorEmailOK() {
        //Arrange
        $xml = self::completeNewsML();
        $user_emailCreatorCorrect = "creator@email.com";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $user_emailCreator = $parseArray[0]['users'][0]['user_email'];

        //Assert
        $this->assertEquals($user_emailCreatorCorrect, $user_emailCreator);
    }

    public function testCreatorQcodeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_qcodeCreatorCorrect = "creator:qcode";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_qcodeCreator = $parseArray[0]['users'][0]['nml2_qcode'];

        //Assert
        $this->assertEquals($nml2_qcodeCreatorCorrect, $nml2_qcodeCreator);
    }

    public function testCreatorUriOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_uriCreatorCorrect = "creator:uri";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_uriCreator = $parseArray[0]['users'][0]['nml2_uri'];

        //Assert
        $this->assertEquals($nml2_uriCreatorCorrect, $nml2_uriCreator);
    }

    public function testContributorLoginOK() {
        //Arrange
        $xml = self::completeNewsML();
        $user_loginContributorCorrect = "Contributor";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $user_loginContributor = $parseArray[0]['users'][1]['user_login'];

        //Assert
        $this->assertEquals($user_loginContributorCorrect, $user_loginContributor);
    }

    public function testContributorDescriptionOK() {
        //Arrange
        $xml = self::completeNewsML();
        $descriptionContributorCorrect = "contributor:role";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $descriptionContributor = $parseArray[0]['users'][1]['description'];

        //Assert
        $this->assertEquals($descriptionContributorCorrect, $descriptionContributor);
    }

    public function testContributorEmailOK() {
        //Arrange
        $xml = self::completeNewsML();
        $user_emailContributorCorrect = "contributor@email.com";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $user_emailContributor = $parseArray[0]['users'][1]['user_email'];

        //Assert
        $this->assertEquals($user_emailContributorCorrect, $user_emailContributor);
    }

    public function testContributorQcodeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_qcodeContributorCorrect = "contributor:qcode";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_qcodeContributor = $parseArray[0]['users'][1]['nml2_qcode'];

        //Assert
        $this->assertEquals($nml2_qcodeContributorCorrect, $nml2_qcodeContributor);
    }

    public function testContributorUriOK() {
        //Arrange
        $xml = self::completeNewsML();
        $nml2_uriContributorCorrect = "contributor:uri";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $nml2_uriContributor = $parseArray[0]['users'][1]['nml2_uri'];

        //Assert
        $this->assertEquals($nml2_uriContributorCorrect, $nml2_uriContributor);
    }

    public function testSubjectQcodeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $qcodeSubjectCorrect = "subject:qcode";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $qcodeSubject = $parseArray[0]['subjects'][0]['qcode'];

        //Assert
        $this->assertEquals($qcodeSubjectCorrect, $qcodeSubject);
    }

    public function testSubjectTextOK() {
        //Arrange
        $xml = self::completeNewsML();
        $textSubjectCorrect = "Subject";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $textSubject = $parseArray[0]['subjects'][0]['name'][0]['text'];

        //Assert
        $this->assertEquals($textSubjectCorrect, $textSubject);
    }

    public function testSubjectLangOK() {
        //Arrange
        $xml = self::completeNewsML();
        $langSubjectCorrect = "subject:lang";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $langSubject = $parseArray[0]['subjects'][0]['name'][0]['lang'];

        //Assert
        $this->assertEquals($langSubjectCorrect, $langSubject);
    }

    public function testSubjectRoleOK() {
        //Arrange
        $xml = self::completeNewsML();
        $roleSubjectCorrect = "subject:role";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $roleSubject = $parseArray[0]['subjects'][0]['name'][0]['role'];

        //Assert
        $this->assertEquals($roleSubjectCorrect, $roleSubject);
    }

    public function testSubjectTypeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $typeSubjectCorrect = "subject:type";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $typeSubject = $parseArray[0]['subjects'][0]['type'];

        //Assert
        $this->assertEquals($typeSubjectCorrect, $typeSubject);
    }

    public function testSubjectUriOK() {
        //Arrange
        $xml = self::completeNewsML();
        $uriSubjectCorrect = "subject:uri";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $uriSubject = $parseArray[0]['subjects'][0]['uri'];

        //Assert
        $this->assertEquals($uriSubjectCorrect, $uriSubject);
    }

    public function testSameAsQcodeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $qcodeSameAsCorrect = "sameAs:qcode";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $qcodeSameAs = $parseArray[0]['subjects'][0]['sameAs'][0]['qcode'];

        //Assert
        $this->assertEquals($qcodeSameAsCorrect, $qcodeSameAs);
    }

    public function testSameAsTextOK() {
        //Arrange
        $xml = self::completeNewsML();
        $textSameAsCorrect = "SameAs";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $textSameAs = $parseArray[0]['subjects'][0]['sameAs'][0]['name'][0]['text'];

        //Assert
        $this->assertEquals($textSameAsCorrect, $textSameAs);
    }

    public function testSameAsLangOK() {
        //Arrange
        $xml = self::completeNewsML();
        $langSameAsCorrect = "sameAs:lang";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $langSameAs = $parseArray[0]['subjects'][0]['sameAs'][0]['name'][0]['lang'];

        //Assert
        $this->assertEquals($langSameAsCorrect, $langSameAs);
    }

    public function testSameAsRoleOK() {
        //Arrange
        $xml = self::completeNewsML();
        $roleSameAsCorrect = "sameAs:role";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $roleSameAs = $parseArray[0]['subjects'][0]['sameAs'][0]['name'][0]['role'];

        //Assert
        $this->assertEquals($roleSameAsCorrect, $roleSameAs);
    }

    public function testSameAsTypeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $typeSameAsCorrect = "sameAs:type";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $typeSameAs = $parseArray[0]['subjects'][0]['sameAs'][0]['type'];

        //Assert
        $this->assertEquals($typeSameAsCorrect, $typeSameAs);
    }

    public function testSameAsUriOK() {
        //Arrange
        $xml = self::completeNewsML();
        $uriSameAsCorrect = "sameAs:uri";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $uriSameAs = $parseArray[0]['subjects'][0]['sameAs'][0]['uri'];

        //Assert
        $this->assertEquals($uriSameAsCorrect, $uriSameAs);
    }

    public function testBroaderQcodeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $qcodeBroaderCorrect = "broader:qcode";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $qcodeBroader = $parseArray[0]['subjects'][0]['broader'][0]['qcode'];

        //Assert
        $this->assertEquals($qcodeBroaderCorrect, $qcodeBroader);
    }

    public function testBroaderTextCorrect() {
        //Arrange
        $xml = self::completeNewsML();
        $textBroaderCorrect = "Broader";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $textBroader = $parseArray[0]['subjects'][0]['broader'][0]['name'][0]['text'];

        //Assert
        $this->assertEquals($textBroaderCorrect, $textBroader);
    }

    public function testBroaderLangOK() {
        //Arrange
        $xml = self::completeNewsML();
        $langBroaderCorrect = "broader:lang";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $langBroader = $parseArray[0]['subjects'][0]['broader'][0]['name'][0]['lang'];

        //Assert
        $this->assertEquals($langBroaderCorrect, $langBroader);
    }

    public function testBroaderRoleOK() {
        //Arrange
        $xml = self::completeNewsML();
        $roleBroaderCorrect = "broader:role";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $roleBroader = $parseArray[0]['subjects'][0]['broader'][0]['name'][0]['role'];

        //Assert
        $this->assertEquals($roleBroaderCorrect, $roleBroader);
    }

    public function testBroaderTypeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $typeBroaderCorrect = "broader:type";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $typeBroader = $parseArray[0]['subjects'][0]['broader'][0]['type'];

        //Assert
        $this->assertEquals($typeBroaderCorrect, $typeBroader);
    }

    public function testBroaderUriOK() {
        //Arrange
        $xml = self::completeNewsML();
        $uriBroaderCorrect = "broader:uri";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $uriBroader = $parseArray[0]['subjects'][0]['broader'][0]['uri'];

        //Assert
        $this->assertEquals($uriBroaderCorrect, $uriBroader);
    }

    public function testPhotoHrefOK() {
        //Arrange
        $xml = self::completeNewsML();
        $hrefCorrect = "http://imageHref";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $href = $parseArray[0]['photo'][0]['href'];

        //Assert
        $this->assertEquals($hrefCorrect, $href);
    }

    public function testPhotoSizeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $sizeCorrect = "12345";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $size = $parseArray[0]['photo'][0]['size'];

        //Assert
        $this->assertEquals($sizeCorrect, $size);
    }

    public function testPhotoWidthOK() {
        //Arrange
        $xml = self::completeNewsML();
        $widthCorrect = "123";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $width = $parseArray[0]['photo'][0]['width'];

        //Assert
        $this->assertEquals($widthCorrect, $width);
    }

    public function testPhotoHeightOK() {
        //Arrange
        $xml = self::completeNewsML();
        $heightCorrect = "12";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $height = $parseArray[0]['photo'][0]['height'];

        //Assert
        $this->assertEquals($heightCorrect, $height);
    }

    public function testPhotoContenttypeOK() {
        //Arrange
        $xml = self::completeNewsML();
        $contenttypeCorrect = "image/jpeg";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $contenttype = $parseArray[0]['photo'][0]['contenttype'];

        //Assert
        $this->assertEquals($contenttypeCorrect, $contenttype);
    }

    public function testPhotoColourspaceOK() {
        //Arrange
        $xml = self::completeNewsML();
        $colourspaceCorrect = "colsp:AdobeRGB";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $colourspace = $parseArray[0]['photo'][0]['colourspace'];

        //Assert
        $this->assertEquals($colourspaceCorrect, $colourspace);
    }

    public function tsetPhotoRenditionOK() {
        //Arrange
        $xml = self::completeNewsML();
        $renditionCorrect = "rnd:highRes";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $rendition = $parseArray[0]['photo'][0]['rendition'];

        //Assert
        $this->assertEquals($renditionCorrect, $rendition);
    }

    public function testImageDescriptionOK() {
        //Arrange
        $xml = self::completeNewsML();
        $imagedescriptionCorrect = "Image description";

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $imagedescription = $parseArray[0]['photo'][0]['description'];

        //Assert
        $this->assertEquals($imagedescriptionCorrect, $imagedescription);
    }

    public function testPubStatusPublish() {
        //Arrange
        $xml = '<newsItem guid="guid" version="1">

                </newsItem>';

        $pubStatusCorrect = 'publish';

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $pubStatus = $parseArray[0]['post']['post_status'];

        //Assert
        $this->assertEquals($pubStatusCorrect, $pubStatus);
    }

    public function testContentMissing() {
        //Arrange
        $xml = '<newsItem>
                    </newsItem>';

        $status_codeCorrect = 400;

        //Act
        $parseArray = newsItemParse::parseNewsML($xml);
        $status_code = $parseArray['status_code'];

        //Assert
        $this->assertEquals($status_codeCorrect, $status_code);
    }

    public function headlineMissingTest() {
        //Arrange
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
        $parseArray = newsItemParse::parseNewsML($xml);
        $status_code = $parseArray['status_code'];

        //Assert
        $this->assertEquals($status_codeCorrect, $status_code);
    }

    public function testGuidMissing() {
        //Arrange
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
        $parseArray = newsItemParse::parseNewsML($xml);
        $status_code = $parseArray['status_code'];


        //Assert
        $this->assertEquals($status_codeCorrect, $status_code);
    }

    public function testVersionMissing() {
        //Arrange
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
        $parseArray = newsItemParse::parseNewsML($xml);
        $status_code = $parseArray['status_code'];


        //Assert
        $this->assertEquals($status_codeCorrect, $status_code);
    }

    public function contentAlternate1() {
        //Arrange
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
        $parseArray = newsitemParse::parseNewsML($xml);
        $post_content = $parseArray[0]['post']['post_content'];

        //Assert
        $this->assertEquals($post_contentCorrect, $post_content);
    }

    public function testContentAlternate2() {
        //Arrange
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
        $parseArray = newsitemParse::parseNewsML($xml);
        $post_content = $parseArray[0]['post']['post_content'];

        //Assert
        $this->assertEquals($post_contentCorrect, $post_content);
    }

    public function testContentAlternate3() {
        //Arrange
        $xml = '<newsItem>
                        <contentSet>
                            <inlineData>Content</inlineData>
                        </contentSet>
                    </newsItem>';

        $post_contentCorrect = "Content";

        //Act
        $parseArray = newsitemParse::parseNewsML($xml);
        $post_content = $parseArray[0]['post']['post_content'];

        //Assert
        $this->assertEquals($post_contentCorrect, $post_content);
    }

    public function testHeadlineAlternate1() {
        //Arrange
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
        $parseArray = newsitemParse::parseNewsML($xml);
        $post_title = $parseArray[0]['post']['post_title'];

        //Assert
        $this->assertEquals($post_titleCorrect, $post_title);
    }

    public function headlineAlternate2Test() {
        //Arrange
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
        $parseArray = newsitemParse::parseNewsML($xml);
        $post_title = $parseArray[0]['post']['post_title'];

        //Assert
        $this->assertEquals($post_titleCorrect, $post_title);
    }

    public function testNewsItemMissing() {
        //Arrange
        $xml = '<newsMessage></newsMessage>';
        $status_codeCorrect = 400;

        //Act
        $parseArray = newsitemParse::parseNewsML($xml);
        $status_code = $parseArray['status_code'];

        //Assert
        $this->assertEquals($status_codeCorrect, $status_code);
    }

    public function testNoPostPayload() {
        //Arrange
        $xml = '';
        $status_codeCorrect = 400;

        //Act
        $parseArray = newsitemParse::parseNewsML($xml);
        $status_code = $parseArray['status_code'];

        //Assert
        $this->assertEquals($status_codeCorrect, $status_code);
    }

}
