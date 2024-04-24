let side = true;


function sidebar() {
    var sidebarElement = document.getElementById('sidebar');
    var con = document.getElementById('con');
    var content = document.getElementById('content');
    if (side) {
        sidebarElement.style.transform = 'translateX(-250px)';
       
        con.style.height = sidebar.clientHeight + 'px';
        content.style.paddingLeft = '10px';


        setTimeout(function() {
            
            sidebarElement.style.display = 'auto';
        }, 1000);
        side = false;
    } 
    else {
        sidebarElement.style.transform = 'translateX(0px)';
        con.style.height = sidebar.clientHeight + 'px';

  
        content.style.paddingLeft = '250px';

        setTimeout(function() {
            sidebarElement.style.display = 'block';
     
        }, 1000);
        
        side = true;
    }
}