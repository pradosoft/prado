<!-- 
	jGrouseDoc template file. Creates Javascript index
	@Copyright (c) 2007 by Denis Riabtchik. All rights reserved. See license.txt and http://jgrouse.com for details@
	$Id: jsindex.xslt 276 2007-12-09 00:50:40Z denis.riabtchik $
-->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0">
	<xsl:param name='fileName' />
	<xsl:param name='rootPath' />
	<xsl:param name='version'/>	
    <xsl:param name='aux_css'>not_specified</xsl:param>
	
	<xsl:output method='text'>
	</xsl:output>
    <xsl:import href="../../common/xslt/common.xslt"/>
	
    <xsl:template match="function|constructor|ifunction|event" mode="JSwriteSummary">
        <xsl:choose>
            <xsl:when test="count(comment/inheritdesc) != 0">
                <xsl:variable name="fn"><xsl:value-of select="comment/inheritdesc/tagContent/@name"/></xsl:variable>
                <xsl:apply-templates select="/jgdoc/items/*[@id=$fn]" mode="JSwriteFuncSummary1">
                    <xsl:with-param name="origName"><xsl:value-of select="@id"/></xsl:with-param>
                    <xsl:with-param name="modifiers"><xsl:value-of select="comment/modifiers/@name"/></xsl:with-param>
                </xsl:apply-templates>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates select="." mode="JSwriteFuncSummary1">
                    <xsl:with-param name="origName"><xsl:value-of select="@id"/></xsl:with-param>
                    <xsl:with-param name="modifiers"><xsl:value-of select="comment/modifiers/@name"/></xsl:with-param>
                </xsl:apply-templates>
            </xsl:otherwise>
        </xsl:choose>       
    </xsl:template>
    
    <xsl:template match="function|constructor|ifunction|event" mode="JSwriteFuncSummary1">
        <xsl:param name="origName"/>
        <xsl:param name="modifiers"/>
        <xsl:value-of select="name()"/><xsl:text> </xsl:text>  
        <xsl:choose>
            <xsl:when test="count(comment/paramSet) != 0">
                <xsl:for-each select="comment/paramSet"><xsl:if test="position() != 1"><xsl:text>\n</xsl:text></xsl:if><xsl:if test="string-length($modifiers) != 0">
                        <xsl:value-of select="$modifiers"/><xsl:text> </xsl:text>
                        </xsl:if>
                        <xsl:if test="count(../../comment/type) != 0">
                        <xsl:apply-templates select="../../comment/type"/>
                        <xsl:text> </xsl:text>
                        </xsl:if> 
                        <xsl:value-of select="../../@localName"/>
                        <xsl:call-template name="writeFunctionParams"> 
                            <xsl:with-param name="funcName"><xsl:value-of select="../../@id"/></xsl:with-param>
                                <xsl:with-param name="paramSetCount"><xsl:value-of select="position()"/></xsl:with-param>
                        </xsl:call-template>
                </xsl:for-each> 
            </xsl:when>
            <xsl:otherwise>
                    <xsl:if test="string-length($modifiers) != 0">
                    <xsl:value-of select="$modifiers"/>
                    <xsl:text> </xsl:text></xsl:if>
                    <xsl:if test="count(comment/type) != 0">                  
                    <xsl:apply-templates select="comment/type"/>
                    <xsl:text> </xsl:text></xsl:if><xsl:value-of select="@localName"/>()</xsl:otherwise>
        </xsl:choose>
    </xsl:template>	
    
    <xsl:template match="class|namespace|struct|interface|object" mode="JSwriteSummary">
        <xsl:if test="count(comment/modifiers) != 0"><xsl:value-of select="comment/modifiers/@name"/><xsl:text> </xsl:text></xsl:if>
        <xsl:value-of select="name()"/><xsl:text> </xsl:text>
        <xsl:value-of select="@localName"/>
    </xsl:template>
    
    
    <xsl:template match="variable|property" mode="JSwriteSummary">
        <xsl:choose>
            <xsl:when test="count(comment/inheritdesc) != 0">
                <xsl:variable name="fn"><xsl:value-of select="comment/inheritdesc/tagContent/@name"/></xsl:variable>
                <xsl:apply-templates select="/jgdoc/items/*[@id=$fn]" mode="JSwriteVarSummary1">
                    <xsl:with-param name="origName"><xsl:value-of select="@id"/></xsl:with-param>
                </xsl:apply-templates>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates select="." mode="JSwriteVarSummary1">
                    <xsl:with-param name="origName"><xsl:value-of select="@id"/></xsl:with-param>
                </xsl:apply-templates>
            </xsl:otherwise>
        </xsl:choose>       
    </xsl:template>
    
    <xsl:template match="variable|property" mode="JSwriteVarSummary1">
        <xsl:param name="origName"/>
            <xsl:value-of select="name()"/><xsl:text> </xsl:text>
            <xsl:if test="count(comment/type) != 0">
            <xsl:apply-templates select="comment/type"/>
            <xsl:text> </xsl:text></xsl:if>
            <xsl:value-of select="@localName" />
    </xsl:template>
    
    	

	<xsl:template match="/">
/*Generated by jGrouseDoc*/
(function()
{
    var data = [<xsl:for-each select="/jgdoc/items/*[@elementType != 'phys_container']//comment"><xsl:sort select="@localName"/><xsl:if test="position() != 1">,</xsl:if>
        <xsl:call-template name="writeJS"/>
    </xsl:for-each>];
    jgdoc.setData(data);
    
})()
	</xsl:template>
	
	<xsl:template name="writeJS">
	   {
	       localName : "<xsl:value-of select="../@localName"/>",
	       fullName : "<xsl:value-of select="../@id"/>",
	       summary : "<xsl:apply-templates select=".." mode="JSwriteSummary"/>",
	       ref : "<xsl:call-template name="writeLink"><xsl:with-param name="refName"><xsl:value-of select="../@id"/></xsl:with-param></xsl:call-template>",
	       parent : "<xsl:value-of select="../@parentName"/>",
	       type : "<xsl:value-of select="name(..)"/>",
	       elementType : "<xsl:value-of select="../@elementType"/>"
	       
	   }
	</xsl:template>
</xsl:stylesheet>
