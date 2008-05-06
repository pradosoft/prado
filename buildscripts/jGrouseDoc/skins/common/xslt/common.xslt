<!-- 
	jGrouseDoc template file. Shared templates.
	@Copyright (c) 2007 by Denis Riabtchik. All rights reserved. See license.txt and http://jgrouse.com for details@
	$Id: common.xslt 515 2008-03-31 19:32:57Z denis.riabtchik $
-->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0">
	
	<xsl:template name="parentPath">
		<xsl:param name="parentName"/>
		<xsl:value-of select="/jgdoc/items/*[@id=$parentName]/@path" />		
	</xsl:template>	 
	
	<xsl:template match="type">
	   <span class="type"><xsl:apply-templates select="*"/></span>
	   <xsl:text> </xsl:text>
	</xsl:template>
	
	<xsl:template match="*" mode="writeFuncSummary">
		<xsl:choose>
			<xsl:when test="count(comment/inheritdesc) != 0">
				<xsl:variable name="fn"><xsl:value-of select="comment/inheritdesc/tagContent/@name"/></xsl:variable>
				<xsl:apply-templates select="/jgdoc/items/*[@id=$fn]" mode="writeFuncSummary1">
					<xsl:with-param name="origName"><xsl:value-of select="@id"/></xsl:with-param>
					<xsl:with-param name="modifiers"><xsl:value-of select="comment/modifiers/@name"/></xsl:with-param>
				</xsl:apply-templates>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="." mode="writeFuncSummary1">
					<xsl:with-param name="origName"><xsl:value-of select="@id"/></xsl:with-param>
                    <xsl:with-param name="modifiers"><xsl:value-of select="comment/modifiers/@name"/></xsl:with-param>
				</xsl:apply-templates>
			</xsl:otherwise>
		</xsl:choose>		
	</xsl:template>
	
	<xsl:template match="*" mode="writeFuncSummary1">
		<xsl:param name="origName"/>
		<xsl:param name="modifiers"/>
		<xsl:choose>
			<xsl:when test="count(comment/paramSet) != 0">
				<xsl:for-each select="comment/paramSet">
					<div class="summaryItemDef">
                        <span class="modifiers"><xsl:value-of select="$modifiers"/></span>
                        <xsl:text> </xsl:text>
						<xsl:apply-templates select="../../comment/type" />
						<xsl:element name="a">
							<xsl:attribute name="href">
								<xsl:call-template name="writeLink">
									<xsl:with-param name="refName"><xsl:value-of select="$origName"/></xsl:with-param>
								</xsl:call-template>
							</xsl:attribute>
							<span class="elementName"><xsl:value-of select="../../@localName"/></span>
						</xsl:element>
						<xsl:call-template name="writeFunctionParams"> 
							<xsl:with-param name="funcName"><xsl:value-of select="../../@id"/></xsl:with-param>
								<xsl:with-param name="paramSetCount"><xsl:value-of select="position()"/></xsl:with-param>
						</xsl:call-template>
					</div>
				</xsl:for-each>	
			</xsl:when>
			<xsl:otherwise>
				<div class="summaryItemDef">
                    <span class="modifiers"><xsl:value-of select="$modifiers"/></span>
                    <xsl:text> </xsl:text>                  
					<xsl:apply-templates select="comment/type" />
					<xsl:element name="a">
						<xsl:attribute name="href">
						  <xsl:call-template name="writeLink">
                            <xsl:with-param name="refName"><xsl:value-of select="$origName"/></xsl:with-param>
                          </xsl:call-template>
						</xsl:attribute>
						<span class="elementName"><xsl:value-of select="@localName"/></span>
					</xsl:element>()
				</div>
			</xsl:otherwise>
		</xsl:choose>
		<div class="summaryItemDesc">
		<xsl:apply-templates select="comment/summary/content"/>
		</div>
	</xsl:template>
	
	<xsl:template match="*" mode="writeVarSummary">
		<xsl:choose>
			<xsl:when test="count(comment/inheritdesc) != 0">
				<xsl:variable name="fn"><xsl:value-of select="comment/inheritdesc/tagContent/@name"/></xsl:variable>
				<xsl:apply-templates select="/jgdoc/items/*[@id=$fn]" mode="writeVarSummary1">
					<xsl:with-param name="origName"><xsl:value-of select="@id"/></xsl:with-param>
                    <xsl:with-param name="modifiers"><xsl:value-of select="comment/modifiers/@name"/></xsl:with-param>
				</xsl:apply-templates>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="." mode="writeVarSummary1">
					<xsl:with-param name="origName"><xsl:value-of select="@id"/></xsl:with-param>
                    <xsl:with-param name="modifiers"><xsl:value-of select="comment/modifiers/@name"/></xsl:with-param>
				</xsl:apply-templates>
			</xsl:otherwise>
		</xsl:choose>		
	</xsl:template>
	
	<xsl:template match="*" mode="writeVarSummary1">
		<xsl:param name="origName"/>
        <xsl:param name="modifiers"/>
		<div class="summaryItemDef">
            <span class="modifiers"><xsl:value-of select="$modifiers"/></span>
            <xsl:text> </xsl:text>
			<xsl:apply-templates select="comment/type" />
			<xsl:element name="a">
				<xsl:attribute name="href">
					<xsl:call-template name="writeLink">
						<xsl:with-param name="refName"><xsl:value-of select="$origName"/></xsl:with-param>
					</xsl:call-template>
				</xsl:attribute>
				<span class="elementName"><xsl:value-of select="@localName" /></span>
			</xsl:element>
		</div> 
		<div class="summaryItemDesc">
			<xsl:apply-templates select="comment/summary/content"/>
		</div>	
	</xsl:template>
	
	<xsl:template match="*" mode="writeLogicalSummary">
		<div class="summaryItemDef">
			<span class="elementName"><xsl:element name="a">
				<xsl:attribute name="href"><xsl:value-of select="$rootPath"/>logical/<xsl:value-of select="@path"/>.html</xsl:attribute>
				<xsl:value-of select="comment/name"/>
			</xsl:element></span>
		</div>
		<div class="summaryItemDesc">
			<xsl:apply-templates select="comment/summary/content"/>
		</div>
	</xsl:template>
	
	<xsl:template name="writeFunctionParams">
		<xsl:param name="funcName"/>
		<xsl:param name="paramSetCount"/>(<xsl:for-each select="/jgdoc/items/*[@id=$funcName]/comment/paramSet[$paramSetCount]/param"><xsl:if test="position() != 1">, </xsl:if><xsl:if test="@optional = 'true'">[</xsl:if><xsl:apply-templates select="type"/><xsl:value-of select="normalize-space(@name)"/><xsl:if test="@optional = 'true'">]</xsl:if></xsl:for-each>)</xsl:template>
	
	<xsl:template name="writeLink">
		<xsl:param name="refName"/>
		<xsl:for-each select="/jgdoc/items/*[@id=$refName]">
			<xsl:value-of select="$rootPath"/><xsl:choose>
				<xsl:when test="@elementType='phys_container'">physical/</xsl:when>
				<xsl:otherwise>logical/</xsl:otherwise>
				</xsl:choose>
				<xsl:choose >
					<xsl:when test="(@elementType='phys_container') or (@elementType='logical_container')"
						><xsl:value-of select="@path"/>.html</xsl:when>
						<xsl:otherwise>
								<xsl:call-template name="parentPath">
									<xsl:with-param name="parentName" select="@parentName"/>
								</xsl:call-template>.html#___<xsl:value-of select="@localName" />						
						</xsl:otherwise>
				</xsl:choose>
		</xsl:for-each>
	</xsl:template>
	
	<xsl:template match="contentSection">
		<xsl:value-of select="." disable-output-escaping="yes"/> 
	</xsl:template>
	
	<xsl:template match="link">
		<xsl:choose>
			<xsl:when test="string-length(@resolvedPath) > 0">
				<xsl:element name="a">
					<xsl:attribute name="href">
						<xsl:call-template name="writeLink">
							<xsl:with-param name="rootPath"><xsl:value-of select="$rootPath"/></xsl:with-param>
							<xsl:with-param name="refName"><xsl:value-of select="@resolvedPath"/></xsl:with-param>
						</xsl:call-template>
					</xsl:attribute><xsl:value-of select="." disable-output-escaping="yes"/></xsl:element>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="content">
		  <xsl:apply-templates />
	</xsl:template>


	<xsl:template match="comment" mode="genCommonAttrs">
		<xsl:if test="count(author/tagContent) != 0">
		    <div class="authors">
			<span class="descSection">Authors:</span>
			<xsl:for-each select="author/tagContent">
				<div class="paddedDetails"><xsl:apply-templates select="."/></div>
			</xsl:for-each>
			</div>
		</xsl:if>
		<xsl:if test="count(see/tagContent) != 0">
		    <div class="seealso">
			<span class="descSection">See also:</span>
			<xsl:for-each select="see/tagContent">
				<div class="paddedDetails"><xsl:apply-templates select="content"/></div>
			</xsl:for-each>
			</div>
		</xsl:if>
		<xsl:if test="count(version) != 0">
		    <div class="version">
			<span class="descSection version">Version:</span>
				<div class="paddedDetails"><xsl:value-of select="$version"/></div>
			</div>
		</xsl:if>
        <xsl:if test="count(timestamp) != 0">
            <div class="generated">
            <span class="descSection">Generated on:</span>
                <div class="paddedDetails"><xsl:value-of select="/jgdoc/project/@timestamp"/> <xsl:text> </xsl:text> <xsl:apply-templates select="version/tagContent"/></div>
            </div>
        </xsl:if>
		<xsl:if test="count(since/tagContent) != 0">
		    <div class="since">
			<span class="descSection">Since:</span>
				<div class="paddedDetails"><xsl:apply-templates select="since/tagContent"/></div>
		    </div>
		</xsl:if>
		<xsl:if test="count(deprecated/tagContent) != 0">
		    <div class="deprecated">
			<span class="descSection">Deprecated</span>
				<div class="paddedDetails"><xsl:apply-templates select="deprecated/tagContent"/></div>
		    </div>
		</xsl:if>
	</xsl:template>



    <!-- new stuff -->
    
    <xsl:template match="function|constructor|ifunction|event" mode="writeSummary">
        <xsl:apply-templates select="." mode="writeFuncSummary"/>
    </xsl:template>
    
    <xsl:template match="variable|property" mode="writeSummary">
        <xsl:apply-templates select="." mode="writeVarSummary"/>
    </xsl:template>

    <xsl:template match="namespace|class|interface|struct|object" mode="writeSummary">
        <xsl:apply-templates select="." mode="writeLogicalSummary"/>
    </xsl:template>
    
    <xsl:template match="module" mode="writeSummary">
	    <div class="summaryItemDef">
	        <span class="elementName"><xsl:element name="a">
	            <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>physical/<xsl:value-of select="@path"/>.html</xsl:attribute>
	            <xsl:value-of select="comment/name"/>
	        </xsl:element></span>
	    </div>
	    <div class="summaryItemDesc">
	        <xsl:apply-templates select="comment/summary/content"/><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
	    </div>    
    </xsl:template>

    
    <xsl:template name="writeRefSummary">
        <xsl:param name="fullName"/>
        <xsl:apply-templates select="/jgdoc/items/*[@id=$fullName]" mode="writeSummary">
        </xsl:apply-templates>
    </xsl:template>
    
    <xsl:template match="children" mode="writeSummary">
        <xsl:param name="elementType"/>
        <xsl:param name="elementName"/>
        <xsl:if test="count(ref[@type=$elementType]) != 0">
            <xsl:element name="div">
                <xsl:attribute name="class">
                    <xsl:text>summaryTable </xsl:text>
                    <xsl:value-of select="$elementType"/>
                </xsl:attribute>
	            <div class="summaryTableHeader" >
	                <span class="objType"><xsl:value-of select="$elementName"/></span> Summary
	            </div>
	            <xsl:for-each select="ref[@type=$elementType]">
	               <div class="summaryItem"> 
		                <xsl:call-template name="writeRefSummary">
		                   <xsl:with-param name="fullName"><xsl:value-of select="@refId"/></xsl:with-param>
		                </xsl:call-template>
	                </div>
	            </xsl:for-each>
            </xsl:element>
	        <p/>
        </xsl:if>
    </xsl:template>
    
    <xsl:template name="writeOverview">
        <xsl:param name="objName"/>
            <div class="overviewItem">
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:call-template name="writeLink">
                            <xsl:with-param name="refName"><xsl:value-of select="$objName"/></xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>
                    <xsl:attribute name="target">classFrame</xsl:attribute>
                    <xsl:choose>
                        <xsl:when test="/jgdoc/items/*[@id=$objName]/@parentName='_GLOBAL_NAMESPACE'">
	                        <xsl:value-of select="/jgdoc/items/*[@id=$objName]/comment/name"/>
                        </xsl:when>
                        <xsl:otherwise>
	                        <xsl:value-of select="/jgdoc/items/*[@id=$objName]/@id"/>
                        </xsl:otherwise>
                    </xsl:choose>                    
                </xsl:element>
            </div>
    </xsl:template>
    
    <xsl:template match="children" mode="writeRefOverview">
        <xsl:param name="elementType"/>
        <xsl:param name="elementName"/>
        <xsl:if test="count(ref[@type=$elementType]) != 0">
	        <div class="overviewTitle"><xsl:value-of select="$elementName"/></div>
	        <xsl:for-each select="ref[@type=$elementType]">
	           <xsl:call-template name="writeOverview">
	               <xsl:with-param name="objName"><xsl:value-of select="@refId"/></xsl:with-param>
	           </xsl:call-template>
	        </xsl:for-each>
        </xsl:if>
    </xsl:template>
    
    <xsl:template name="navbarPhys">
        <xsl:param name="isStartup">no</xsl:param>
        <div class="navbar">
            <xsl:if test="$isStartup='yes'">
            <div class="navbaritem">
               <xsl:element name="a">
                   <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>overview-summary-log.html</xsl:attribute>
                   Logical View
               </xsl:element>
            </div>
            </xsl:if>
            <xsl:if test="$isStartup != 'yes'">
            <div class="navbaritem">
               <xsl:element name="a">
                   <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>overview-summary.html</xsl:attribute>
                   Start
               </xsl:element>
            </div>
            </xsl:if>        
            <div class="navbaritem">
               <xsl:element name="a">
                   <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>jgindex.html</xsl:attribute>
                   Index
               </xsl:element>
            </div>
            <div class="navbaritem">
               <xsl:element name="a">
                   <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>jgsearch.html</xsl:attribute>
                   Search
               </xsl:element>
            </div>
        </div>        
    </xsl:template>
    
    <xsl:template name="navbarLog">
        <xsl:param name="isStartup">no</xsl:param>
	    <div class="navbar">
	        <xsl:if test="$isStartup='yes'">
            <div class="navbaritem">
               <xsl:element name="a">
                   <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>overview-summary.html</xsl:attribute>
                   Physical View
               </xsl:element>
            </div>
	        </xsl:if>
            <xsl:if test="$isStartup != 'yes'">
            <div class="navbaritem">
               <xsl:element name="a">
                   <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>overview-summary-log.html</xsl:attribute>
                   Start
               </xsl:element>
            </div>
            </xsl:if>
	        <div class="navbaritem">
	           <xsl:element name="a">
	               <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>jgindex.html</xsl:attribute>
	               Index
	           </xsl:element>
	        </div>
	        <div class="navbaritem">
               <xsl:element name="a">
                   <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>jgsearch.html</xsl:attribute>
                   Search
               </xsl:element>
	        </div>
	    </div>      
    </xsl:template>
    
    <xsl:template match="legacies" mode="inheritance">
        <xsl:param name="memberName"/>
        <xsl:param name="memberType"/>
         <xsl:for-each select="legacy[count(legacyItem[@type=$memberType]) != 0]">
             <div class="summaryTable">
                <div class="summaryTableHeader">
                   <xsl:value-of select="$memberName"/> inherited from 
                         <xsl:element name="a">
                             <xsl:attribute name="href">
                                 <xsl:call-template name="writeLink">
                                     <xsl:with-param name="refName"><xsl:value-of select="@parent"/></xsl:with-param>
                                 </xsl:call-template>
                             </xsl:attribute>
                             <xsl:value-of select="@parent" />
                         </xsl:element> 
                 </div>
                 <div class="inheritanceSummary">
                         <xsl:for-each select="legacyItem[@type=$memberType]">
                             <xsl:if test="position() != 1">
                                 <xsl:text>, </xsl:text>
                             </xsl:if>
                             <xsl:element name="a">
                                 <xsl:attribute name="href">
                                     <xsl:call-template name="writeLink">
                                         <xsl:with-param name="refName"><xsl:value-of select="@idRef"/></xsl:with-param>
                                     </xsl:call-template>
                                 </xsl:attribute>
                                 <xsl:value-of select="@ref" />
                             </xsl:element> 
                             <xsl:text> </xsl:text>
                         </xsl:for-each>
                 </div>
             </div>
             <br/>
         </xsl:for-each>    
    </xsl:template>
    
    <xsl:template match="ref" mode="writeDetails">
        <xsl:param name="memberName"/>
        <xsl:param name="memberType"/>
        <xsl:param name="original"/>
        <xsl:param name="modifiers"/>
        <xsl:variable name="curName"><xsl:value-of select="@refId"/></xsl:variable>
        
        <xsl:apply-templates select="/jgdoc/items/*[@id=$curName]" mode="writeDetails">
            <xsl:with-param name="memberName"><xsl:value-of select="$memberName"/></xsl:with-param>
            <xsl:with-param name="memberType"><xsl:value-of select="$memberType"/></xsl:with-param>
            <xsl:with-param name="original"><xsl:value-of select="$original"/></xsl:with-param>
            <xsl:with-param name="modifiers"><xsl:value-of select="$modifiers"/></xsl:with-param>
        </xsl:apply-templates>
    
    </xsl:template>  
    
    <xsl:template match="function|constructor|ifunction|event" mode="writeDetails">
        <xsl:param name="memberName"/>
        <xsl:param name="memberType"/>
        <xsl:param name="original"/>
        <xsl:param name="modifiers"/>
        <xsl:variable name="effMods">
          <xsl:choose>
              <xsl:when test="$original = 'true'"><xsl:value-of select="comment/modifiers/@name"/></xsl:when>
              <xsl:otherwise><xsl:value-of select="$modifiers"/></xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="count(comment/inheritdesc) != 0">
                <xsl:variable name="fn"><xsl:value-of select="comment/inheritdesc/tagContent/@name"/></xsl:variable>
                <xsl:apply-templates select="/jgdoc/items/*[@id=$fn]" mode="writeDetails">
                    <xsl:with-param name="memberName"><xsl:value-of select="$memberName"/></xsl:with-param>
                    <xsl:with-param name="memberType"><xsl:value-of select="$memberType"/></xsl:with-param>
                    <xsl:with-param name="modifiers"><xsl:value-of select="$effMods"/></xsl:with-param>
                    <xsl:with-param name="original">false</xsl:with-param>
                </xsl:apply-templates>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates select="." mode="writeDetails1">
                    <xsl:with-param name="memberName"><xsl:value-of select="$memberName"/></xsl:with-param>
                    <xsl:with-param name="memberType"><xsl:value-of select="$memberType"/></xsl:with-param>
                    <xsl:with-param name="modifiers"><xsl:value-of select="$effMods"/></xsl:with-param>
                </xsl:apply-templates>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    <xsl:template match="variable|property" mode="writeDetails">
        <xsl:param name="original"/>
        <xsl:param name="modifiers"/>
        <xsl:variable name="effMods">
          <xsl:choose>
              <xsl:when test="$original = 'true'"><xsl:value-of select="comment/modifiers/@name"/></xsl:when>
              <xsl:otherwise><xsl:value-of select="$modifiers"/></xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        
        <xsl:choose>
            <xsl:when test="count(comment/inheritdesc) != 0">
                <xsl:variable name="fn"><xsl:value-of select="comment/inheritdesc/tagContent/@name"/></xsl:variable>
                <xsl:apply-templates select="/jgdoc/items/*[@id=$fn]" mode="writeVarDetails1"/>
                <xsl:with-param name="modifiers"><xsl:value-of select="$effMods"/></xsl:with-param>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates select="." mode="writeVarDetails1">
                    <xsl:with-param name="modifiers"><xsl:value-of select="$effMods"/></xsl:with-param>
                </xsl:apply-templates>
            </xsl:otherwise>    
        </xsl:choose>
    </xsl:template>
    
    <xsl:template match="variable|property" mode="writeVarDetails1">
        <xsl:param name="modifiers"/>
        <xsl:apply-templates select="comment/srcUrl" mode="writeDetails1"/>
        <h3>
            <xsl:element name="a">
                <xsl:attribute name="name">___<xsl:value-of select='@localName'/>
                </xsl:attribute>
                <span class="objType"><xsl:value-of select="name()"/></span> <xsl:text> </xsl:text> <span class="modifiers"><xsl:value-of select="$modifiers"/></span> <xsl:text> </xsl:text> 
                <xsl:apply-templates select="comment/type" /><span class="objName"><xsl:value-of select='@localName' /></span>
            </xsl:element>
        </h3>
        <div class="comment">
        <xsl:apply-templates select='comment/commentContent/content'/>
        </div>
        <xsl:apply-templates select="comment" mode="genCommonAttrs" />
        <xsl:apply-templates select="." mode="physOwner">
            <xsl:with-param name="testNode"><xsl:value-of select='@id'/></xsl:with-param>
        </xsl:apply-templates>                      
        <hr />
    </xsl:template>
    
    <xsl:template match="param" mode="writeDetails1">
        <div class="param paddedDetails">
            <code><xsl:if test="@optional = 'true'">[</xsl:if><xsl:value-of select="@name"/><xsl:if test="@optional = 'true'">]</xsl:if></code> <xsl:text> </xsl:text>
            <xsl:apply-templates select="content"/>
            <xsl:if test="count(paramOptions/paramoption) != 0">
            <ul class="paramoption">
            <xsl:apply-templates select="paramOptions/paramoption" mode="writeDetails1"/>
            </ul>
            </xsl:if>
        </div>    
    </xsl:template>
    
    <xsl:template match="modifiers" mode="writeDetails1">
                <span class="modifiers"><xsl:value-of select="@name"/></span>
                <xsl:text> </xsl:text>
    </xsl:template>
    
    <xsl:template match="paramoption" mode="writeDetails1">
        <li >
            <code>
                <xsl:if test="@optional = 'true'">[</xsl:if>
                <xsl:apply-templates select="modifiers" mode="writeDetails1"/>
                <xsl:apply-templates select="type" />
                <xsl:value-of select="@name"/>
                <xsl:if test="@optional = 'true'">]</xsl:if>
            </code> <xsl:text> </xsl:text>
            <xsl:apply-templates select="content"/>
        </li>
    </xsl:template>
    
    <xsl:template match="comment/srcUrl" mode="writeDetails1">
        <xsl:element name="a">
            <xsl:attribute name="class">srcUrlLink</xsl:attribute>
            <xsl:attribute name="href"><xsl:value-of select="."/></xsl:attribute>
            view source
        </xsl:element>
    </xsl:template>
    
    <xsl:template match="function|constructor|ifunction|event" mode="writeDetails1">
        <xsl:param name="memberName"/>
        <xsl:param name="memberType"/>
        <xsl:param name="modifiers"/>
        <xsl:apply-templates select="comment/srcUrl" mode="writeDetails1"/>
        <h3>
            <xsl:element name="a">
                <xsl:attribute name="name">___<xsl:value-of select='@localName'/>
                </xsl:attribute>
                <span class="objType"><xsl:value-of select="$memberType"/></span><xsl:text> </xsl:text>
                <span class="objName"><xsl:value-of select='@localName'/></span>
            </xsl:element>
        </h3>
        <xsl:choose>
            <xsl:when test="count(comment/paramSet) != 0">
                <xsl:for-each select="comment/paramSet">
                    <div>
                        <code>
                        <span class="modifiers"><span class="modifiers"><xsl:value-of select="$modifiers"/></span></span>
                        <xsl:text> </xsl:text>
                        <xsl:apply-templates select="../../comment/type" />
                        <xsl:value-of select="../../@localName"/>
                        <xsl:call-template name="writeFunctionParams"> 
                            <xsl:with-param name="funcName"><xsl:value-of select="../../@id"/></xsl:with-param>
                                <xsl:with-param name="paramSetCount"><xsl:value-of select="position()"/></xsl:with-param>
                        </xsl:call-template> 
                        </code> 
                    </div> 
                </xsl:for-each>  
            </xsl:when>
            <xsl:otherwise>
                <div>
                    <code>
                    <span class="modifiers"><xsl:value-of select="$modifiers"/></span>
                    <xsl:text> </xsl:text>
                    <xsl:apply-templates select="comment/type" />
                    <xsl:value-of select="@localName"/>()
                    </code>
                </div>
            </xsl:otherwise>                    
        </xsl:choose>
        <div class="comment">
            <xsl:apply-templates select="comment/commentContent/content" />
        </div>
        <xsl:if test="count(comment/paramSet) != 0">
            <div class="parameters">
	            <span class="descSection">Parameters:</span><br/>
	            <xsl:for-each select='comment/paramSet'>
	                <div class="paramset">
	                    <xsl:apply-templates select="paramSetDesc/content"/>
	                </div>
	                <div>
	                    <xsl:apply-templates select="param" mode="writeDetails1"/>
	                </div>
	            </xsl:for-each>
            </div>
        </xsl:if>
        <xsl:if test="count(comment/returns/tagContent) > 0">
            <div class="returns">
	            <span class="descSection">Returns:</span><br/>
	            <div class="paddedDetails">
	                <xsl:apply-templates select='comment/returns/tagContent'/>
	                <xsl:if test="count(comment/returns/paramOptions/paramoption) != 0">
	                    <ul class="paramoption">
	                    <xsl:apply-templates select="comment/returns/paramOptions/paramoption" mode="writeDetails1"/>
	                    </ul>
	                </xsl:if>
	            </div>
            </div>
        </xsl:if>
        <xsl:if test="count(comment/throws/tagContent) > 0">
            <div class="throws">
	            <span class="descSection">Throws:</span><br/>
	            <xsl:for-each select='comment/throws/tagContent/content'>
	                <div class="paddedDetails"><xsl:apply-templates select='.'/></div>
	            </xsl:for-each>
            </div>
        </xsl:if>
        
        <xsl:apply-templates select="comment" mode="genCommonAttrs" />

        <xsl:apply-templates select="." mode="physOwner">
            <xsl:with-param name="testNode"><xsl:value-of select='@id'/></xsl:with-param>
        </xsl:apply-templates>
        <hr />
    </xsl:template>
    
    
    <xsl:template name="writeDetails">
        <xsl:param name="memberName"/>
        <xsl:param name="memberType"/>
        <xsl:param name="objectName"/>
        <xsl:if
                test="count(/jgdoc/items/*[@id=$objectName]/children/ref[@type=$memberType]) != 0">
                <xsl:element name="div">
                    <xsl:attribute name="class"><xsl:text>details </xsl:text><xsl:value-of select="$memberType"/></xsl:attribute>
	                <h2 class="sectionHeader"><xsl:value-of select="$memberName"/> Details</h2>
	                <xsl:for-each
	                    select="/jgdoc/items/*[@id=$objectName]/children/ref[@type=$memberType]">
	                    <xsl:apply-templates select="." mode="writeDetails">
	                        <xsl:with-param name="memberName"><xsl:value-of select="$memberName"/></xsl:with-param>
	                        <xsl:with-param name="memberType"><xsl:value-of select="$memberType"/></xsl:with-param>
	                        <xsl:with-param name="original">true</xsl:with-param>
	                    </xsl:apply-templates>
	                </xsl:for-each>
                </xsl:element>
            </xsl:if>   
    </xsl:template>           


    <xsl:template match = "*" mode="physOwner">
        <xsl:param name="testNode"/>
        <xsl:variable name='owner' select="@parentName"/>
        
        <xsl:variable name='parentPhys'><xsl:value-of select='/jgdoc/items/*[@id=$owner]/@physOwner'/></xsl:variable>
        <xsl:variable name='phys'><xsl:value-of select='@physOwner'/></xsl:variable>
        <xsl:if test="$phys != $parentPhys">
            <span class="definedIn">Defined in </span><xsl:element name="a">
                <xsl:attribute name="href">
                    <xsl:value-of select="$rootPath"/>physical/<xsl:value-of select="/jgdoc/items/*[@id=$phys]/@path"/>.html</xsl:attribute>
                <xsl:value-of select="/jgdoc/items/*[@id=$phys]/comment/name"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>
    
    <xsl:template name="writeCss">
        <xsl:param name="rootPath"/>
        <xsl:param name="aux_path"/>
	    <xsl:element name="link">
	        <xsl:attribute name="rel">stylesheet</xsl:attribute>
	        <xsl:attribute name="type">text/css</xsl:attribute>
	        <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>jgdoc.css</xsl:attribute>
	    </xsl:element>
	      <xsl:element name="link">
	        <xsl:attribute name="rel">stylesheet</xsl:attribute>
	        <xsl:attribute name="type">text/css</xsl:attribute>
	        <xsl:attribute name="href"><xsl:value-of select="$rootPath"/>theme.css</xsl:attribute>
	    </xsl:element>
	    <xsl:if test="$aux_css != 'not_specified'">
	        <xsl:element name="link">
	            <xsl:attribute name="rel">stylesheet</xsl:attribute>
	            <xsl:attribute name="type">text/css</xsl:attribute>
	            <xsl:attribute name="href"><xsl:value-of select="$rootPath"/><xsl:value-of select="$aux_css"/></xsl:attribute>
	        </xsl:element>
	    </xsl:if>
    
    </xsl:template>

</xsl:stylesheet>
