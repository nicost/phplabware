<h3>reports</h3>
<p>To define a report you will need to make a file (using you favorite text editor) to be used as a template for the report.  The report template consists of three parts: 
<ul>
<li>A header section that will be displayed only once.  The end of the header section is indicated by: <br><pre>
&lt;!--fields--&gt;
</pre></li>
<li>A section that will be displayed for every record in the current search result set.  The text will displayed as is, except that the SQL columnnames of the table, preceded with a '$' sign will be replaced with the text as displayed by phplabware for that record and column.  The SQL columnname preceded with a '%' sign will be replaced by the actual content of that record and column in the database.  As an example:<br><pre>
<b>$counter. </b>$title,$image,$title,$link,$tests,$file<br />
</pre></li>
<li>A footer section that will be displayed once at the bottom of the report. It should be preceded with:<br><pre>
&lt;!--/fields--&gt;
</pre><br>
In the footer section, every SQL columnname preceded with the '&' sign will be replaced by the sum of all entries with that columnname.
</li>
</ul>
<p>Both the header and footer sections are optional.  As an example, look at the file reports.txt.</p>


<h3>URLs linking into phplabware</h3>
<p>As of version 0.5 of phplabware, login information can be provided in the URL itself, allowing direct linking to any recod within phplabware.  For this to work, you need to set this option in 'System' -> 'settings' (Allow logins that have, etc..) and you will need to check the box next to 'Allow URL-based login' in the user's settings.  Now, a URL of the form:</p>
<p>http://mysite/phplabware/login.php?user=guest&pwd=guest&javascript_enabled=true</p>
<p>will automate the login procedure.  It is adviceable to set the last option (javascript_enable=true) using javascript.</p>
