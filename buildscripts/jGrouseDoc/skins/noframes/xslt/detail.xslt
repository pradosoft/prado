<!-- 
	jGrouseDoc template file. 
	Creates content for top-left frame with all namespaces
	@Copyright (c) 2007 by Denis Riabtchik. All rights reserved. See license.txt and http://jgrouse.com for details@
	$Id: detail.xslt 339 2008-01-21 00:21:30Z denis.riabtchik $
-->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0">
	<xsl:param name='rootPath' />
    <xsl:param name='trueRootPath' />
    <xsl:param name="projectDesc"/>
    <xsl:param name="projectName"/>
	<xsl:param name='version'/>	
    <xsl:param name='objectName' />
    <xsl:param name='aux_css'>not_specified</xsl:param>
    <xsl:param name="navSection"/>
	
	<xsl:output method='HTML' doctype-public="-//W3C//DTD HTML 4.01//EN"
		doctype-system="http://www.w3.org/TR/html4/strict.dtd">

	</xsl:output> 
    <xsl:import href="../../common/xslt/common.xslt"/>
    <xsl:import href="nfcommon.xslt"/>
    
    
    <xsl:template match="/jgdoc/items/*">
        <xsl:variable name="objType"><xsl:value-of select="name()"/></xsl:variable>
        <div>
                <xsl:apply-templates select="comment/srcUrl" mode="writeDetails1"/>
                <H2>
                    <span class="objType">
                    <xsl:choose> 
                        <xsl:when test="$objType = 'namespace'">Namespace </xsl:when>
                        <xsl:when test="$objType = 'class'">Class </xsl:when>
                        <xsl:when test="$objType = 'struct'">Structure </xsl:when>
                        <xsl:when test="$objType = 'interface'">Interface </xsl:when>
                        <xsl:when test="$objType = 'object'">Object </xsl:when>
                    </xsl:choose>
                    </span>
                    <xsl:value-of 
                        select="comment/name" /> 
                </H2>
                <xsl:if test="($objType = 'class') or ($objType = 'interface')">
                    <xsl:if test="count(comment/extends) > 0">
                        <div class="extends">
                        <span class="descSection">Extends </span>
                        <xsl:for-each select="comment/extends/tagContent">
                            <xsl:if test="position() > 1">
                                <xsl:text>, </xsl:text>
                            </xsl:if> 
                            <xsl:apply-templates select="content"/>
                        </xsl:for-each>
                        </div>
                    </xsl:if>
                </xsl:if> 
                <xsl:if test="($objType = 'class')">
                    <xsl:if test="count(comment/implements) > 0">
                        <div class="implements">
                        <span class="descSection">Implements </span>
                        <xsl:for-each select="comment/implements/tagContent">
                            <xsl:if test="position() > 1">
                                <xsl:text>, </xsl:text>
                            </xsl:if>
                            <xsl:apply-templates select="content"/>
                        </xsl:for-each>
                        </div>
                    </xsl:if>
                </xsl:if>
                
                <xsl:if test="count(/jgdoc/items/*[(name() = 'class' or name() = 'interface') and comment/extends/tagContent[@name=$objectName]]) != 0">
                    <div class="subclasses">
                        <div class="descSection">
                            <xsl:if test="name() = 'class'">Direct Known Subclasses:</xsl:if>
                            <xsl:if test="name() = 'interface'">All Known Subinterfaces:</xsl:if>
                        </div>
                        <div class="paddedDetails">
                           <xsl:for-each select="/jgdoc/items/*[comment/extends/tagContent[@name=$objectName]]">
                               <xsl:if test="position() > 1">
                                <xsl:text>, </xsl:text>
                            </xsl:if>
                            <xsl:element name="a">
                                <xsl:attribute name="href">
                                    <xsl:call-template name="writeLink">
                                        <xsl:with-param name="refName"><xsl:value-of select="@id"/></xsl:with-param>
                                    </xsl:call-template>
                                </xsl:attribute>
                                <span><xsl:value-of select="@id"/></span>
                            </xsl:element>
                           </xsl:for-each>
                        </div>
                    </div>
                </xsl:if>

                <xsl:if test="count(/jgdoc/items/class[comment/implements/tagContent[@name=$objectName]]) != 0">
                    <div>
                        <div>
                            All Known Implementing Classes:
                        </div>
                        <div>
                           <xsl:for-each select="/jgdoc/items/class[comment/implements/tagContent[@name=$objectName]]">
                               <xsl:if test="position() > 1">
                                <xsl:text>, </xsl:text>
                            </xsl:if>
                            <xsl:element name="a">
                                <xsl:attribute name="href">
                                    <xsl:call-template name="writeLink">
                                        <xsl:with-param name="refName"><xsl:value-of select="@id"/></xsl:with-param>
                                    </xsl:call-template>
                                </xsl:attribute>
                                <span><xsl:value-of select="@id"/></span>
                            </xsl:element>
                           </xsl:for-each>
                        </div>
                    </div>
                </xsl:if>

                
                <hr/>
                <!-- description -->
                <div>
                    <code>
                        <span class="modifiers"><xsl:value-of select="comment/modifiers/@name"/></span>
                        <xsl:text> </xsl:text>
                        <span class="objType"><xsl:value-of select="$objType"/></span> 
                        <xsl:text> </xsl:text>
                        <b><xsl:value-of select="comment/name"/></b>
                 </code>
                </div>
                <xsl:for-each
                    select='comment/commentContent/content'>
                    <div class="comment">
                    <xsl:apply-templates select='.'/>
                    </div>
                </xsl:for-each>
                <xsl:apply-templates select="comment" mode="genCommonAttrs" />
                <xsl:for-each select="/jgdoc/items/*[@id=$objectName and @physOwner != '']">
                    <span class="definedIn">Defined in </span>
                    <xsl:element name='a'>
                        <xsl:attribute name='href'>
                            <xsl:value-of select="$rootPath"/>physical/<xsl:value-of select="/jgdoc/items/*[@id=/jgdoc/items/*[@id=$objectName]/@physOwner]/@path"/>.html</xsl:attribute>
                        <xsl:value-of select='/jgdoc/items/*[@id=/jgdoc/items/*[@id=$objectName]/@physOwner]/comment/name'/>
                    </xsl:element>
                </xsl:for-each>
                <hr/> 
                <p />
                
                <xsl:variable name="nested"><xsl:if test="@elementType = 'logical_container' and name() != 'namespace'">Nested</xsl:if></xsl:variable>
                
                <xsl:apply-templates select="children" mode="writeSummary">
                   <xsl:with-param name="elementType">class</xsl:with-param>
                   <xsl:with-param name="elementName"><xsl:value-of select="$nested"/> Class</xsl:with-param>
                </xsl:apply-templates>  

                <xsl:apply-templates select="children" mode="writeSummary">
                   <xsl:with-param name="elementType">interface</xsl:with-param>
                   <xsl:with-param name="elementName"><xsl:value-of select="$nested"/> Interface</xsl:with-param>
                </xsl:apply-templates>  
                
                <xsl:apply-templates select="children" mode="writeSummary">
                   <xsl:with-param name="elementType">object</xsl:with-param>
                   <xsl:with-param name="elementName"><xsl:value-of select="$nested"/> Object</xsl:with-param>
                </xsl:apply-templates>
                
                <xsl:apply-templates select="children" mode="writeSummary">
                   <xsl:with-param name="elementType">struct</xsl:with-param>
                   <xsl:with-param name="elementName"><xsl:value-of select="$nested"/> Structure</xsl:with-param>
                </xsl:apply-templates>  


                <!-- Variables -->
                <xsl:apply-templates select="children" mode="writeSummary">
                    <xsl:with-param name="elementType">variable</xsl:with-param>
                    <xsl:with-param name="elementName">Variable</xsl:with-param>
                </xsl:apply-templates>              


                <xsl:apply-templates select="legacies" mode="inheritance">
                    <xsl:with-param name="memberName">Variables</xsl:with-param>
                    <xsl:with-param name="memberType">variable</xsl:with-param>
                </xsl:apply-templates>                


                <!-- properties -->
                <xsl:apply-templates select="children" mode="writeSummary">
                    <xsl:with-param name="elementType">property</xsl:with-param>
                    <xsl:with-param name="elementName">Property</xsl:with-param>
                </xsl:apply-templates>     
                

                <xsl:apply-templates select="legacies" mode="inheritance">
                    <xsl:with-param name="memberName">Properties</xsl:with-param>
                    <xsl:with-param name="memberType">property</xsl:with-param>
                </xsl:apply-templates>                


                
                <!-- Constructors -->
                <xsl:apply-templates select="children" mode="writeSummary">
                    <xsl:with-param name="elementType">constructor</xsl:with-param>
                    <xsl:with-param name="elementName">Constructor</xsl:with-param>
                </xsl:apply-templates>                
                
                <!-- Functions -->
                <xsl:apply-templates select="children" mode="writeSummary">
                    <xsl:with-param name="elementType">function</xsl:with-param>
                    <xsl:with-param name="elementName">Function</xsl:with-param>
                </xsl:apply-templates>
                
                <!-- Events -->
                <xsl:apply-templates select="children" mode="writeSummary">
                    <xsl:with-param name="elementType">event</xsl:with-param>
                    <xsl:with-param name="elementName">Event</xsl:with-param>
                </xsl:apply-templates>
                
                <xsl:apply-templates select="legacies" mode="inheritance">
                    <xsl:with-param name="memberName">Functions</xsl:with-param>
                    <xsl:with-param name="memberType">function</xsl:with-param>
                </xsl:apply-templates>                
                
                                
                <!-- Function interfaces -->
                <xsl:apply-templates select="children" mode="writeSummary">
                    <xsl:with-param name="elementType">ifunction</xsl:with-param>
                    <xsl:with-param name="elementName">Function Interface</xsl:with-param>
                </xsl:apply-templates>
                
                <!-- ============== Details coming here =================== -->                

                <!-- variables -->              
                <xsl:call-template name="writeDetails">
                    <xsl:with-param name="memberName">Variable</xsl:with-param>
                    <xsl:with-param name="memberType">variable</xsl:with-param>
                    <xsl:with-param name="objectName"><xsl:value-of select="$objectName"/></xsl:with-param>
                </xsl:call-template>

                <!-- properties -->
                <xsl:call-template name="writeDetails">
                    <xsl:with-param name="memberName">Property</xsl:with-param>
                    <xsl:with-param name="memberType">property</xsl:with-param>
                    <xsl:with-param name="objectName"><xsl:value-of select="$objectName"/></xsl:with-param>
                </xsl:call-template>
                                
                
                <!-- constructors -->
                <xsl:call-template name="writeDetails">
                    <xsl:with-param name="memberName">Constructor</xsl:with-param>
                    <xsl:with-param name="memberType">constructor</xsl:with-param>
                    <xsl:with-param name="objectName"><xsl:value-of select="$objectName"/></xsl:with-param>
                </xsl:call-template>
                
                <!-- functions -->
                <xsl:call-template name="writeDetails">
                    <xsl:with-param name="memberName">Function</xsl:with-param>
                    <xsl:with-param name="memberType">function</xsl:with-param>
                    <xsl:with-param name="objectName"><xsl:value-of select="$objectName"/></xsl:with-param>
                </xsl:call-template>    
                
                <!-- events -->
                <xsl:call-template name="writeDetails">
                    <xsl:with-param name="memberName">Event</xsl:with-param>
                    <xsl:with-param name="memberType">event</xsl:with-param>
                    <xsl:with-param name="objectName"><xsl:value-of select="$objectName"/></xsl:with-param>
                </xsl:call-template>            

                <!-- function interfaces -->
                <xsl:call-template name="writeDetails">
                    <xsl:with-param name="memberName">Function Interface</xsl:with-param>
                    <xsl:with-param name="memberType">ifunction</xsl:with-param>
                    <xsl:with-param name="objectName"><xsl:value-of select="$objectName"/></xsl:with-param>
                </xsl:call-template>        
        </div>
    </xsl:template>
    

	<xsl:template match="/">
		<xsl:comment>Generated by jGrouseDoc</xsl:comment>
		<html>
		    <head>
                 <title><xsl:value-of select="$projectName"/></title>
                 <script>

					(function()
					{
					    var loc = window.location.protocol + '//' + window.location.hostname + window.location.pathname;
                        loc = loc.split('\\');
                        loc = loc.join('/');					    
					    loc = loc.split('/');
					    loc.pop();
					    loc = loc.join('/');
					    loc += '/' + '<xsl:value-of select="$trueRootPath"/>';
					
					    document.write("&lt;base href='" + loc + "'&gt;&lt;/base&gt;");
					})();
                 
                 </script>
                 
			     <xsl:call-template name="writeCss">
			         <xsl:with-param name="rootPath"></xsl:with-param>
			         <xsl:with-param name="aux_css"><xsl:value-of select="$aux_css"/></xsl:with-param>
			     </xsl:call-template>
                 <script type="text/javascript" src="navTree.js"></script>
                 <script type="text/javascript" src="jgdoc.js"></script>
			</head>
			<body>
                <div class="startup" id="startup">
                     <div class="banner" id="banner">
			             <h1 class="projectName">
			                 <xsl:element name="a">
			                     <xsl:attribute name="href">.</xsl:attribute>
			                     <xsl:value-of select="$projectName"/>
			                 </xsl:element>
			             </h1>
			             <div class="bar">
			                 version <xsl:value-of select="$version"/>
			             </div>
			         </div>
                     <div class="content" id="docContent">
		                  <div class="block">
		                        <div id="searchBlock">
					                <div class="search" >
					                    <div class="searchLabel">Search:</div>
					                    <div class="searchBlock" style="height:100%">
							                <input id="jgsSearchString" type="text" size="60" class="jgdSearchString"/>
							                <div id="jgsSearchPanel" class="jgsSearchPanel" style="display:none">
								                <div  class="jgdSearchRect" >
								                    <div id="jgsSearchResults">
								                        Loading....
								                    </div>
								                </div>
							                   <div id="jgsInfo" class="jgsInfo" >No selection</div>
		                                    </div>
		                                 </div>
					                </div>
				                </div>
				                <div id="docScroll" class="docScroll">
				                    <div>
					                    <hr/>
						                <xsl:apply-templates select="/jgdoc/items/*[@id=$objectName]"/>
					                </div>			                    
				                </div>
                	       </div>
		             </div>
                     <xsl:call-template name="navigationPane">
                          <xsl:with-param name="elementType"><xsl:value-of select="$navSection"/></xsl:with-param>
                     </xsl:call-template>  
				</div>	
			</body>
            <script type="text/javascript">
                jgdoc.Searcher.start();
                jgdoc.NavTree.initialize('<xsl:value-of select="$objectName"/>');
            </script>			
			<script type="text/javascript" src="jsindex.js">
            </script>
		</html>
	</xsl:template>



</xsl:stylesheet>
