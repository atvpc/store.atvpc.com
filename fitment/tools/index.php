<ul>
	<li>Amasty:
	<ul>
		<li><a href="dump.php?dump=vehicles">List All Vehicles in Amasty</a>
		<li><a href="dump.php?dump=skus">List All SKUs and their fitments in Amasty</a>:<br>
		    <em>differs from above in that it includes SKUs, and that duplicate vehicles are listed due to multiple SKUs fitting one vehicle</em>

	</ul>

	<li>Fitment Location:
	<ul>
		<li><a href="upload.php">Upload a CSV with new fitment locations</a>
		<li><a href="log.php">Upload Log</a>
		<li><a href="dump.php?dump=fitments">List all the Fitment Locations currently in the DB</a>
		<li><a href="dump.php?dump=missing">List SKUs missing fitment location</a>:<br>
                    <em>compares fitment location DB against Amasty's DB (can be slow to load, be patient)</em>
	</ul>

	<li><a href="update_finder_img.php">Update Make Logos on Parts Finder</a>:<br>
            <em>Run after adding a new SKU in Amasty or when logos are not displaying correctly</em>
</ul>
