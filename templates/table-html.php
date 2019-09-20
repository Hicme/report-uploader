<div class="preview-table-wrapper">
  <table>
    <tbody>
      <?php
        foreach ( $datas as $row ) {
          ?>
          <tr>
            <?php
              foreach ( $row as $td ) {
                ?>
                <td>
                  <?php echo $td; ?>
                </td>
                <?php
              }
            ?>
          </tr>
          <?php
        }
      ?>
    </tbody>
  </table>
</div>