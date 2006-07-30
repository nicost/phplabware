<h3>How to write a report template</h3>
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


