<!-- 
	jGrouseDoc template file. Renders search pane
	@Copyright (c) 2007 by Denis Riabtchik. All rights reserved. See license.txt and http://jgrouse.com for details@
	$Id: nfcommon.xslt 276 2007-12-09 00:50:40Z denis.riabtchik $
-->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0">
	
    <xsl:template name="navigationPane">
        <xsl:param name="elementType"/>
	    <div class="navigation" id="navigation">
	       <xsl:element name="ul">
	           <xsl:if test="$elementType = 'logical'">
	               <xsl:attribute name="class">navContent</xsl:attribute>
	           </xsl:if>
               <xsl:if test="$elementType != 'logical'">
                   <xsl:attribute name="class">navContent closed</xsl:attribute>
               </xsl:if>
	           <li>
			       <div class="navTree">
			           <h2 onclick="jgdoc.NavPanel.clicked(event)">
			             <a href="javascript:jgdoc.NavPanel.dummy()">Navigation</a></h2>
			           <ul class="navContent2">
			               <li>
					           <div>
					               <a class="openAll" href="javascript:jgdoc.NavTree.onOpenAll()">Open All</a>
					               <a class="closeAll" href="javascript:jgdoc.NavTree.onCloseAll()">Close All</a>
					           </div>
					           
					           <div>
					               <ul id = "content" class="contents">
					                   Loading...
					               </ul>
					           </div>
					       </li>
					   </ul>
			       </div>
			   </li>
		   </xsl:element>
		   <xsl:element name="ul">
               <xsl:if test="$elementType = 'file'">
                   <xsl:attribute name="class">navContent</xsl:attribute>
               </xsl:if>
               <xsl:if test="$elementType != 'file'">
                   <xsl:attribute name="class">navContent closed</xsl:attribute>
               </xsl:if>
               <li>
                   <div class="fileTree">
                       <h2 onclick="jgdoc.NavPanel.clicked(event)">
                            <a href="javascript:jgdoc.NavPanel.dummy()">Files</a></h2>
                       <ul class="navContent2">
                           <li>
				                <xsl:for-each select="/jgdoc/items/file[@isModuleFile='false']">
				                    <xsl:sort select="@id"/>
				                    <div>
				                        <xsl:element name="a">
				                            <xsl:attribute name="href">physical/<xsl:value-of select="@path"/>.html</xsl:attribute>
				                            <xsl:value-of select="comment/name"/>
				                        </xsl:element>
				                    </div>
				                </xsl:for-each> 
                           </li>
                       </ul>
                   </div>
               </li>
           </xsl:element>		       
           <xsl:element name="ul">
               <xsl:if test="$elementType = 'module'">
                   <xsl:attribute name="class">navContent</xsl:attribute>
               </xsl:if>
               <xsl:if test="$elementType != 'module'">
                   <xsl:attribute name="class">navContent closed</xsl:attribute>
               </xsl:if>
               <li>
                   <div class="moduleTree">
                       <h2 onclick="jgdoc.NavPanel.clicked(event)">
                        <a href="javascript:jgdoc.NavPanel.dummy()">Modules</a></h2>
                       <ul class="navContent2">
                           <li>
				                <xsl:for-each select="/jgdoc/items/module">
				                    <xsl:sort select="@id"/>
				                    <div>
				                        <xsl:element name="a">
				                            <xsl:attribute name="href">physical/<xsl:value-of select="@path"/>.html</xsl:attribute>
				                            <xsl:value-of select="comment/name"/>
				                        </xsl:element>
				                    </div>
				                </xsl:for-each> 
                           </li>
                       </ul>
                   </div>
               </li>
            </xsl:element>            
	    </div>
    </xsl:template>
	
</xsl:stylesheet>
