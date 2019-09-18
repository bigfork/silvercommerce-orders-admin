<% include \\SilverCommerce\\OrdersAdmin\\Email\\Includes\\EmailHead %>

<% with $Order %>
    <h1><%t Orders.OrderStatusUpdate "Order Status Update" %></h1>

    <p><%t Orders.OrderMarkedAs 'Order {ordernumber} has been marked as {status}' ordernumber=$FullRef status=$Status %></p>

    <% if $Items.exists %>
        <hr/>

        <h2><%t Orders.Items "Items" %></h2>

        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: left"><%t Orders.Details "Details" %></th>
                    <th style="text-align: right"><%t Orders.QTY "Qty" %></th>
                    <th style="text-align: right"><%t Orders.Price "Price" %></th>
                </tr>
            </thead>

            <tbody><% loop $Items %>
                <tr>
                    <td>
                        {$Title} <% if $StockID %>($StockID)<% end_if %><br/>
                        <em>$CustomisationHTML</em>
                    </td>
                    <td style="text-align: right">{$Quantity}</td>
                    <td style="text-align: right">{$UnitPrice.Nice}</td>
                </tr>
            <% end_loop %></tbody>
            
            <tfoot>
                <tr><td colspan="3">&nbsp;</td></tr>
                
                <% if $DiscountTotal.RAW > 0 || $Discount %><tr>
                    <td colspan="2" style="text-align: right;">
                        <strong>
                            <%t Orders.Discount "Discount" %>
                            <% if $Discount %>($Discount)<% end_if %>
                        </strong>
                    </td>
                    <td style="text-align: right;">$DiscountTotal.Nice</td>
                </tr><% end_if %>

                <% if $PostagePrice.RAW > 0 %>
                    <tr>
                        <td colspan="2" style="text-align: right;">
                            <strong><%t Orders.Postage "Postage" %></strong>
                        </td>
                        <td style="text-align: right;">$PostagePrice.Nice</td>
                    </tr>
                <% end_if %>
                
                <% if $TaxTotal %>
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong><%t Orders.SubTotal "Sub Total" %></strong>
                    </td>
                    <td style="text-align: right;">$SubTotal.Nice</td>
                </tr>
                
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong><%t Orders.Tax "Tax" %></strong>
                    </td>
                    <td style="text-align: right;">$TaxTotal.Nice</td>
                </tr>
                <% end_if %>
                
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong><%t Orders.Total "Total" %></strong>
                    </td>
                    <td style="text-align: right;">$Total.Nice</td>
                </tr>
            </tfoot>
        </table>
    <% end_if %>
    
    <hr/>

    <h2><%t Orders.CustomerDetails "Customer Details" %></h2>

    <p>
        <%t Orders.Name "Name" %>: {$FirstName} {$Surname}<br/>
        <% if $PhoneNumber %><%t Orders.Phone "Phone" %>: {$PhoneNumber}<br/><% end_if %>
        <% if $Email %><%t Orders.Email "Email" %>: <a href="mailto:{$Email}">{$Email}</a><br/><% end_if %>
    </p>

    <% if $isDeliverable %> 
        <hr/>
        
        <h2><%t Orders.DeliveryDetails 'Delivery Details' %></h2>

        <p>
            <% if $DeliveryCompany %>$DeliveryCompany<br/><% end_if %>
            {$DeliveryFirstnames} {$DeliverySurname}<br/>
            {$DeliveryAddress1},<br/>
            <% if $DeliveryAddress2 %>{$DeliveryAddress2},<br/><% end_if %>
            {$DeliveryCity},<br/>
            {$DeliveryPostCode},<br/>
            {$DeliveryCountryFull}
        </p>
    <% end_if %>
<% end_with %>

<% include \\SilverCommerce\\OrdersAdmin\\Email\\Includes\\EmailFooter %>
