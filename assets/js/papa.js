
   
    jQuery(document).ready(function($){
        

        //digital marketing packages
        $('#inclusion-pack').click(function(e){
           e.preventDefault();
           pickMarketingPackage('inclusion-pack')   
        })

        $('#premium-pack').click(function(e){
           e.preventDefault()
           pickMarketingPackage('premium-pack')
        })

        $('#ecommerce-pack').click(function(e){
            e.preventDefault()
            pickMarketingPackage('ecommerce-pack')
        })

        
         
        //website packages
        $('#basic-site').click(function(e){
            e.preventDefault();
            var package = 'basic-site';
            pickMarketingPackage(package)

        })

        $('#package-site').click(function(e){
            e.preventDefault();
            var package = 'package-site';
            pickMarketingPackage(package)
        })

        $('#ecommerce-site').click(function(e){
            e.preventDefault();
            var package = 'ecommerce-site';
            pickMarketingPackage(package)
        })

        
        var pickMarketingPackage = function(packagePlan){
            
             const adminUrl = '/wp-admin/admin-ajax.php',
                   checkoutUrl = 'https://www.helloUUU.com/checkout/';
        
            $.post(adminUrl,{
               'action':'package_item_action',
               'post_type': 'POST',
               'package':packagePlan 
            }, function(result){
                if(result.success === true){
                   window.location.href = checkoutUrl
                }else{
                   alert('Failed to add the product to cart')   
                }
           })
        }


        //what about teh contact form.
        $('#contact-form').submit(function(e){
            
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: '/wp-admin/admin-ajax.php',
                data: {
                   'action': 'contact_form',
                   'name': $('#name').val(),
                   'email' : $('#email').val(),
                   'message' : $('#message').val(),
                   'website' : $('#website').val(),
                   'answer' : $('#answer').val()
                },
                success: function(response){
                    console.log('let result', response)
                }
            })
        })


    })

    


