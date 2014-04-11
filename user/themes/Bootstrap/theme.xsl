<?xml version="1.0" encoding="UTF-8"?>
<!--
    Document   : theme.xsl
    Created on : 28 March 2014, 15:29
    Author     : Half-Shot
    Description:
        Purpose of transformation follows.
-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="html"/>
    <xsl:template match="telement[@id='Panel']">
        <div class="panel panel-default">
        <xsl:if test="./variable/title">
            <div class="panel-heading">
              <h3 class="panel-title"><xsl:value-of select="./variable/title"/></h3>
            </div>
        </xsl:if>
        <div class="panel-body">
            <xsl:value-of select="./variable/body"/>
        </div>
      </div>
    </xsl:template>
    <xsl:template match="telement[@id='VerticalNavbar']">
        <ul class="nav nav-pills nav-stacked">
        <xsl:for-each select="./variable/variable">
            <li>
                <xsl:if test="./active = 1">
                    <xsl:attribute name="class">active</xsl:attribute>
                </xsl:if>
                <a>
                    <xsl:attribute name="href">
                        <xsl:value-of select="./url"/>
                    </xsl:attribute>
                    <xsl:value-of select="./text"/>
                </a>
            </li>
        </xsl:for-each>
        </ul>
    </xsl:template>
    <xsl:template match="telement[@id='Title']">
        <h1>
            <xsl:value-of select="./variable/title"/>
            <small>
                <xsl:value-of select="./variable/subtitle"/>
            </small>
        </h1>
    </xsl:template>
    <xsl:template match="telement[@id='Navbar']">
        <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
            <div class="container-fluid">
                <div class="navbar-header">
                    <a class="navbar-brand">
                        <xsl:attribute name="href"><xsl:value-of select="./variable/variable/url"/></xsl:attribute>
                        <xsl:value-of select="./variable/variable/text"/>
                    </a>
                </div>
                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
                        <xsl:for-each select="./variable/variable[position() != 1]">
                            <li>
                                <xsl:if test="./active = 1">
                                    <xsl:attribute name="class">active</xsl:attribute>
                                </xsl:if>
                                <a>
                                    <xsl:attribute name="href"><xsl:value-of select="./url"/></xsl:attribute>
                                    <xsl:value-of select="./text"/>
                                </a>
                            </li>
                        </xsl:for-each>
                    </ul>
                </div>
            </div>
        </nav>
    </xsl:template>
    <xsl:template match="telement[@id='Post-Breadcrumbs']">
        <ol class="breadcrumb">
           <xsl:for-each select="./variable">
            <li>
                <a><xsl:value-of select="./variable"/></a>
            </li>
           </xsl:for-each>
        </ol>
    </xsl:template>
    <xsl:template match="telement[@id='LabelValuePairs']">
        <xsl:for-each select="./variable/variable">
            <xsl:value-of select="./label"/>
            <span class="label label-info">
                <xsl:value-of select="./data"/>
            </span>
            <br></br>
        </xsl:for-each>
    </xsl:template>
    <xsl:template match="telement[@id='Form']">
        <form>
            <xsl:attribute name="name"><xsl:value-of select="./variable/name"/></xsl:attribute>
            <xsl:attribute name="action"><xsl:value-of select="./variable/action"/></xsl:attribute>
            <xsl:attribute name="method"><xsl:value-of select="./variable/method"/></xsl:attribute>
            <xsl:attribute name="formtarget"><xsl:value-of select="./variable/formtarget"/></xsl:attribute>
            <xsl:attribute name="onsubmit"><xsl:value-of select="./variable/onsubmit"/></xsl:attribute>
            <xsl:attribute name="id"><xsl:value-of select="./variable/id"/></xsl:attribute>
            <xsl:for-each select="./variable/elements/variable">
               <xsl:call-template name="FormElement"/>
               <br></br>
            </xsl:for-each>
        </form>
    </xsl:template>
    <xsl:template name="FormElement" match="telement[@id='FormElement']">
        <div class="input-group">
            <xsl:if test="./label">
                <span class="input-group-addon"><xsl:value-of select="./label"/></span>
            </xsl:if>
            <xsl:choose>
                <xsl:when test="./type = 'button'">
                    <button>
                        <xsl:attribute name="name"><xsl:value-of select="./name"/></xsl:attribute>
                        <xsl:attribute name="onclick"><xsl:value-of select="./onclick"/></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                        <xsl:if test="./readonly = 1">
                            <xsl:attribute name="disabled"/>
                        </xsl:if>
                        <xsl:if test="./toggle = 1">
                            <xsl:attribute name="data-toggle">button</xsl:attribute>
                        </xsl:if>
                        <xsl:attribute name="class">btn form-control <xsl:value-of select="./class"/></xsl:attribute>
                        <xsl:value-of select="./value"/>
                    </button>
                </xsl:when>
                <xsl:otherwise>
                <input>
                    <xsl:attribute name="name"><xsl:value-of select="./name"/></xsl:attribute>
                    <xsl:attribute name="type"><xsl:value-of select="./type"/></xsl:attribute>
                    <xsl:attribute name="value"><xsl:value-of select="./value"/></xsl:attribute>
                    <xsl:attribute name="onclick"><xsl:value-of select="./onclick"/></xsl:attribute>
                    <xsl:attribute name="id"><xsl:value-of select="./id"/></xsl:attribute>
                    <xsl:if test="./readonly = 1">
                        <xsl:attribute name="readonly"/>
                    </xsl:if>
                    <xsl:if test="./required = 1">
                        <xsl:attribute name="required"/>
                    </xsl:if>
                    <xsl:attribute name="placeholder"><xsl:value-of select="./placeholder"/></xsl:attribute>
                    <xsl:attribute name="class">form-control <xsl:value-of select="./class"/></xsl:attribute>
                </input>
                </xsl:otherwise>
            </xsl:choose>
        </div>
    </xsl:template>
    <xsl:template name="InputElement" match="telement[@id='InputElement']">
        <xsl:for-each select="./variable">
           <xsl:call-template name="FormElement"/>
        </xsl:for-each>
    </xsl:template>
    <xsl:template name="ErrorScreen" match="telement[@id='ErrorScreen']">
            <xsl:if test="./variable/severity &lt; 1">
                <div class="panel panel-info">
                    <div class="panel-heading">Oh Noes - An error occured!</div>
                      <div class="panel-body">
                          <p>
                              <b>Time:</b>
                              <xsl:value-of select="./variable/time"/>
                          </p>
                          <p>
                              <b>Category:</b>
                              <xsl:value-of select="./variable/category"/>
                          </p>
                          <p>
                              <xsl:value-of select="./variable/message"/>
                          </p>
                      </div>
                </div>
            </xsl:if>
            <xsl:if test="./variable/severity = 2">
                <div class="panel panel-warning">
                    <div class="panel-heading">Oh Noes - An error occured!</div>
                      <div class="panel-body">
                          <p>
                              <b>Time:</b>
                              <xsl:value-of select="./variable/time"/>
                          </p>
                          <p>
                              <b>Category:</b>
                              <xsl:value-of select="./variable/category"/>
                          </p>
                          <p>
                              <xsl:value-of select="./variable/message"/>
                          </p>
                      </div>
                </div>
            </xsl:if>
            <xsl:if test="./variable/severity &gt; 3">
                <div class="panel panel-danger">
                      <div class="panel-heading">Oh Noes - An error occured!</div>
                      <div class="panel-body">
                          <p>
                              <b>Time:</b>
                              <xsl:value-of select="./variable/time"/>
                          </p>
                          <p>
                              <b>Category:</b>
                              <xsl:value-of select="./variable/category"/>
                          </p>
                          <p>
                              <xsl:value-of select="./variable/message"/>
                          </p>
                      </div>
                </div>
            </xsl:if>
    </xsl:template>
    <xsl:template name="Modal" match="telement[@id='Modal']">
        <!-- Modal -->
        <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <xsl:attribute name="id">
                <xsl:value-of select="./variable/id"/>
            </xsl:attribute>
            <xsl:attribute name="aria-labelledby">
                <xsl:value-of select="./variable/label"/>
            </xsl:attribute>
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove-circle"></span></button>
                    <h4 class="modal-title"><xsl:value-of select="./variable/title"/></h4>
                </div>
                <div class="modal-body">
                    <xsl:value-of select="./variable/body"/>
                </div>
                <div class="modal-footer">
                    <xsl:value-of select="./variable/footer"/>
                </div>
              </div>
            </div>
        </div>
    </xsl:template>
    <xsl:template name="Button" match="telement[@id='Button']">
          <button type="button">
                <xsl:attribute name="name"><xsl:value-of select="./variable/name"/></xsl:attribute>
                <xsl:attribute name="id"><xsl:value-of select="./variable/id"/></xsl:attribute>
                <xsl:attribute name="class">btn <xsl:value-of select="./variable/class"/></xsl:attribute>
                <xsl:attribute name="onclick"><xsl:value-of select="./variable/onclick"/></xsl:attribute>
                <xsl:if test="./variable/readonly = 1">
                    <xsl:attribute name="disabled"/>
                </xsl:if>
                <xsl:if test="./variable/toggle = 1">
                    <xsl:attribute name="data-toggle">button</xsl:attribute>
                </xsl:if>
                <xsl:value-of select="./variable/value"/>
          </button>
    </xsl:template>
</xsl:stylesheet>
