<?php $footer_video_link = $footer_video_link ?? true; ?>
<div id="footer" align="bottom">
	<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
		<tbody>
			<tr>
				<td align="left" id="copyright">
					© Copyright 2014, under
					<a href="gnu_gpl.txt" style="color: WHITE; text-decoration: none;" target="_blank">
						GNU General Public License
					</a>
				</td>
<?php if ($footer_video_link): ?>				<td align="center" id="video_link">
					Getting Bored? Watch a
					<a href="javascript:open_overlay();" style="color: #c4dcf5">
						Video</a>
					to pass time!
				</td>
<?php endif; ?>
				<td align="right" id="developer" >
					Quiz Designed &amp; Developed by :
					<a href="mailto: rahul_jain@live.in" class="flink" style="color: #c4dcf5">
						Rahul Jain<div id="dev_info">1139234/CSE/6thSEM</div>
					</a>
				</td>
			</tr>
		</tbody>
	</table>
</div>
