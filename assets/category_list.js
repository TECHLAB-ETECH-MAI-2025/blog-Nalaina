alert('category list ok');
import $ from 'jquery';
import 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

$(document).ready(function() {
    // Initialize DataTable for the category list
    const categorieTable = $('#categorie-table').DataTable({
        responsive: true,
        serverSide: true,
        processing: true,
        ajax: {
            url: '/api/categorie/datatable',
            type: 'POST',
        },
        columns: [
            { data: 'id'},
            { data: 'title' },
            { data: 'description' },
            { data: 'createdAt' },
            { data: 'actions', title: 'Actions', orderable: false, searchable: false }
            
        ],
        language: {
				url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
        },
        order: [[0, 'desc']],
    });
    // recherche en temps réel
    const $searchInput = $('#search-categorie');
    const $searchResults = $('#search-result');
    let searchTimeout;
    $searchInput.on('input', function(){
        const query = $(this).val().trim();
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            $searchResults.removeClass('show').html('');
            return;
        }
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: "/api/categories/search",
                type: "GET",
                data: { q: query },
                dataType: "json",
                success: function(response){
                    if (response.results && response.results.length > 0) {
                        let html = '';
                        response.results.forEach(function(result) {
                            html += `
                                ${result.title}
                                ${result.description}
                                
                            `;
                        });
                        $searchResults.html(html).addClass('show');
                    } else {
                        $searchResults.html('Aucun résultat trouvé').addClass('show');
                    }
                }
            })
        }, 500);

        $(document).on('click', '.search-item', function() {
            const categoryId = $(this).data('id');
            if (categoryId) {
                window.location.href = `/categories/${categoryId}`;
            }
        });
        $(document).on('click', function(event) {
            if (!$(event.target).closest('.search-container').length) {
                $searchResults.removeClass('show');
            }
        });
    })
});