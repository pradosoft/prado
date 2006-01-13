Prado.RicoLiveGrid = Class.create();
Prado.RicoLiveGrid.prototype = Object.extend(Rico.LiveGrid.prototype,
{
	initialize : function(tableId, options)
	{
	     this.options = {
                tableClass:           $(tableId).className || '',
                loadingClass:         $(tableId).className || '',
                scrollerBorderRight: '1px solid #ababab',
                bufferTimeout:        20000,
                sortAscendImg:        'images/sort_asc.gif',
                sortDescendImg:       'images/sort_desc.gif',
                sortImageWidth:       9,
                sortImageHeight:      5,
                ajaxSortURLParms:     [],
                onRefreshComplete:    null,
                requestParameters:    null,
                inlineStyles:         true,
				visibleRows:		  10,
				totalRows:			  0,
				initialOffset:		  0
		};
		Object.extend(this.options, options || {});

      //this.ajaxOptions = {parameters: null};
      //Object.extend(this.ajaxOptions, ajaxOptions || {});

      this.tableId     = tableId; 
      this.table       = $(tableId);

      this.addLiveGridHtml();

      var columnCount  = this.table.rows[0].cells.length;
      this.metaData    = new Rico.LiveGridMetaData(this.options.visibleRows, this.options.totalRows, columnCount, options);
      this.buffer      = new Rico.LiveGridBuffer(this.metaData);

      var rowCount = this.table.rows.length;
      this.viewPort =  new Rico.GridViewPort(this.table, 
                                            this.table.offsetHeight/rowCount,
                                            this.options.visibleRows,
                                            this.buffer, this);
      this.scroller    = new Rico.LiveGridScroller(this,this.viewPort);
      this.options.sortHandler = this.sortHandler.bind(this);

      if ( $(tableId + '_header') )
         this.sort = new Rico.LiveGridSort(tableId + '_header', this.options)

      this.processingRequest = null;
      this.unprocessedRequest = null;

      //this.initAjax(url);
      if (this.options.initialOffset >= 0) 
	  {
         var offset = this.options.initialOffset;
            this.scroller.moveScroll(offset);
            this.viewPort.scrollTo(this.scroller.rowToPixel(offset));            
         if (this.options.sortCol) {
             this.sortCol = options.sortCol;
             this.sortDir = options.sortDir;
         }
         var grid = this;
		 setTimeout(function(){
			 grid.requestContentRefresh(offset);
		 },100);
      }
	},

   fetchBuffer: function(offset) 
   {
      if ( this.buffer.isInRange(offset) &&
         !this.buffer.isNearingLimit(offset)) {
         return;
         }
      if (this.processingRequest) {
          this.unprocessedRequest = new Rico.LiveGridRequest(offset);
         return;
      }
      var bufferStartPos = this.buffer.getFetchOffset(offset);
      this.processingRequest = new Rico.LiveGridRequest(offset);
      this.processingRequest.bufferOffset = bufferStartPos;   
      var fetchSize = this.buffer.getFetchSize(offset);
      var partialLoaded = false;
      
     // var queryString
    //  if (this.options.requestParameters)
       //  queryString = this._createQueryString(this.options.requestParameters, 0);
		var param = 
	   {
			'page_size' : fetchSize,
			'offset' : bufferStartPos
	   };
		if(this.sortCol)
	   {
			Object.extend(param,
		   {
				'sort_col': this.sortCol,
				'sort_dir': this.sortDir
			});
	   }
        /*queryString = (queryString == null) ? '' : queryString+'&';
        queryString  = queryString+'id='+this.tableId+'&page_size='+fetchSize+'&offset='+bufferStartPos;
        if (this.sortCol)
            queryString = queryString+'&sort_col='+escape(this.sortCol)+'&sort_dir='+this.sortDir;

        this.ajaxOptions.parameters = queryString;

       ajaxEngine.sendRequest( this.tableId + '_request', this.ajaxOptions );
		*/
		Prado.Callback(this.tableId, param, this.ajaxUpdate.bind(this), this.options);
       this.timeoutHandler = setTimeout( this.handleTimedOut.bind(this), this.options.bufferTimeout);

   },

   ajaxUpdate: function(result, output) 
   {
      try {
         clearTimeout( this.timeoutHandler );
         this.buffer.update(result,this.processingRequest.bufferOffset);
         this.viewPort.bufferChanged();
      }
      catch(err) {}
      finally {this.processingRequest = null; }
      this.processQueuedRequest();
   }
});

Object.extend(Rico.LiveGridBuffer.prototype,
{
   update: function(newRows, start) 
  {
     if (this.rows.length == 0) { // initial load
         this.rows = newRows;
         this.size = this.rows.length;
         this.startPos = start;
         return;
      }
      if (start > this.startPos) { //appending
         if (this.startPos + this.rows.length < start) {
            this.rows =  newRows;
            this.startPos = start;//
         } else {
              this.rows = this.rows.concat( newRows.slice(0, newRows.length));
            if (this.rows.length > this.maxBufferSize) {
               var fullSize = this.rows.length;
               this.rows = this.rows.slice(this.rows.length - this.maxBufferSize, this.rows.length)
               this.startPos = this.startPos +  (fullSize - this.rows.length);
            }
         }
      } else { //prepending
         if (start + newRows.length < this.startPos) {
            this.rows =  newRows;
         } else {
            this.rows = newRows.slice(0, this.startPos).concat(this.rows);
            if (this.rows.length > this.maxBufferSize) 
               this.rows = this.rows.slice(0, this.maxBufferSize)
         }
         this.startPos =  start;
      }
      this.size = this.rows.length;
   }
});


Object.extend(Rico.GridViewPort.prototype,
{
   populateRow: function(htmlRow, row) 
   {
	   if(isdef(htmlRow))
	   {
		  for (var j=0; j < row.length; j++) {
			 htmlRow.cells[j].innerHTML = row[j]
		  }
	   }
   }
});