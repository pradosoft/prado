<div id="comments">

<com:TRepeater ID="comments">
	<prop:HeaderTemplate>
		<h2 class="comment_header">Comments 
			<span style="font-size:0.8em">( <a href="#add_comments">Add your comments</a> )</span></h2>
	</prop:HeaderTemplate>
	<prop:ItemTemplate>
		<div class="comment_item comment_item<%# $this->ItemIndex%2 %>">
			<span class="number" id="comment_<%# $this->ItemIndex+1 %>"><%# $this->ItemIndex+1 %></span>
			<span class="email">
				<%# str_replace(array('@','.'),array(' at ',' dot '), strtoupper($this->DataItem['email'])) %>
			</span>
			<span class="date">
				<com:TDateFormat Value=<%# intval($this->DataItem['date_added']) %> />
			</span>
			<div class="comment">
				<com:TSafeHtml>
				<com:TMarkdown TextHighlighter.CssClass="source">
					<%# $this->DataItem['comment'] %>
				</com:TMarkdown>
				</com:TSafeHtml>
			</div>
		</div>
	</prop:ItemTemplate>
</com:TRepeater>

<com:TMultiView ID="multiView1" ActiveViewIndex="0">
	<com:TView ID="view1">
		<div id="add_comments" class="add_comments">
			<h3>Post a comment</h3>
			<p><strong>Note:</strong> 
				Please only use the comments in relation to this page for
				<ul> 
					<li>questions/critcisms/suggestions on the documentation,</li>
					<li>small notes that can solve or clarify a particular problem or task.</li>
				</ul>
				If you experience errors please <a href="http://trac.pradosoft.com/newticket">file a ticket</a> 
				or <a href="http://www.pradosoft.com/forum/">ask at the forum</a>.
				Please use the <a href="http://pradosoft.com/wiki/index.php/Main_Page">Prado wiki</a> for longer pieces and detailed solutions</a>.
			</p>
			<p>Comments will be periodically reviewed, integrated into the documentation and removed.
			You may use <a href="?page=Markdown">markdown syntax</a> in your comment. </p>

			<div class="comment_email">
			<com:TLabel ID="email_label" Text="Email:" ForControl="email"/>
			<com:TTextBox ID="email" />
			<com:TRequiredFieldValidator
				ControlToValidate="email"
				Display="Dynamic"
				ErrorMessage="An email address is required." />
			<com:TEmailAddressValidator	
				ControlToValidate="email" 
				CheckMXRecord="false"
				Display="Dynamic"
				ErrorMessage="Please provide your email address."/>
			</div>
			<div class="comment_content">
				<com:TLabel ID="content_label" Text="Comment:" ForControl="content"/>
				<com:TTextBox ID="content" TextMode="MultiLine"/>
				<div class="please_add">
				<com:TRequiredFieldValidator
					ControlToValidate="content"
					Display="Dynamic"
					ErrorMessage="Please add your comment." />
				</div>
			</div>
			<com:TPlaceHolder Visible=<%= strlen($this->content->Text) %> >
			<div class="comment_preview">
				<h3 style="margin:0">Preview comment</h3>
					<div class="comment">
						<com:TSafeHtml>
							<com:TMarkdown TextHighlighter.CssClass="source">
							<%= $this->content->Text %>
							</com:TMarkdown>
						</com:TSafeHtml>
					</div>
				</div>
			</com:TPlaceHolder>			
			<div class="add_comment">
				<com:TButton ID="previewComment" Text="Preview Comment" />
				<com:TButton ID="addComment" Text="Add Comment" OnClick="addComment_Clicked"/>
			</div>
		</div>
	</com:TView>
	<com:TView ID="view2">
		<div class="comment_added">
		<div class="thank">Thank you, your comment is quequed for moderation.</div>
		<div class="comment_preview">
			<h3 style="margin:0">Preview comment</h3>
			<div class="comment">
			<com:TSafeHtml>
				<com:TMarkdown TextHighlighter.CssClass="source">
				<%= $this->content->Text %>
				</com:TMarkdown>
			</com:TSafeHtml>
			</div>
		</div>
		</div>
	</com:TView>
</com:TMultiView>
</div>