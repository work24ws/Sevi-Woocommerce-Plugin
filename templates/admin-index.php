<div id="wpbody-content">
    <div class="wrap">
        <h1 class="wp-heading-inline">Sevi Transactions Dashboard</h1>
        <hr class="wp-header-end">

        <?php
        global $wpdb;

        $orders1 = wc_get_orders( array(
            'limit'        => -1, // Query all orders
            'payment_method' => 'sevi',
            'orderby'      => 'date',
            'order'        => 'DESC',
        ));

        $cancelled = '';
        $processed = '';
        $pending = '';

        $count1 = 0;

        $total1=0;
        foreach($orders1 as $o)
        {
            $user = get_user_by('id',$o->get_user_id());

            if ( $order->has_status('completed') )
            {
                $processed .= '<a href="post.php?post='.$o->get_order_number().'&action=edit">Order #'.$o->get_order_number().'</a><br>';
                $total1 += $o->get_total('');
            }elseif ( $order->has_status('processing') )
            {
                $pending .= '<a href="post.php?post='.$o->get_order_number().'&action=edit">Order #'.$o->get_order_number().'</a><br>';
                $total1 += $o->get_total('');
            }elseif ( $order->has_status('cancelled') )
            {
                $cancelled .= '<a href="post.php?post='.$o->get_order_number().'&action=edit">Order #'.$o->get_order_number().'</a><br>';
            }
        }

        $count = sizeof($orders1);

        $widget = '<br><br><h2>Sevi Transaction Stats</h2>
<table border="0" width="100%">
    <tr>
        <td width="50%"><b>Total Orders Placed: </b>'.$count.'</td>
        <td width="50%"><b>Total Amount: </b>$'.number_format($total1).'</td>
    </tr>
</table>
<br>
<hr>
<br>
<h2>Sevi Transaction Records</h2>
<table border="0" width="100%">
    <tr>
        <td width="33%"><b>Cancelled Orders</b></td>
        <td width="33%"><b>Processed Orders</b></td>
        <td width="33%"><b>Pending Orders</b></td>
    </tr>
    <tr>
        <td width="33%" style="vertical-align:top">'.$cancelled.'</td>
        <td width="33%" style="vertical-align:top">'.$processed.'</td>
        <td width="33%" style="vertical-align:top">'.$pending.'</td>
    </tr>
</table>
';

        echo $widget;
        ?>
    </div>

</div>