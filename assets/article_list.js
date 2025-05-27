import $ from 'jquery';
	import 'datatables.net';
	import 'datatables.net-bs5';
	import 'datatables.net-responsive-bs5';

	$(document).ready(function() {
		// Initialisation de DataTables
		const articlesTable = $('#articles-table').DataTable({
			processing: true,
			serverSide: true,
			responsive: true,
			ajax: {
				url: '/api/article/datatable',
				type: 'POST'
			},
			columns: [
				{ data: 'id' },
				{ data: 'title' },
				{ data: 'categories' },
				{ data: 'commentsCount' },
				{ data: 'likesCount' },
				{ data: 'createdAt' },
				{ data: 'actions',title:'Actions', orderable: false, searchable: false }
			],
			columnDefs: [
				{
					targets: 1, // Colonne "Titre"
					render: function(data, type, row) {
						return data; // le HTML du lien est déjà généré côté serveur
					}
				},
				{
					targets: 6, // Colonne "Actions"
					render: function(data, type, row) {
						return data; // idem, HTML déjà généré
					}
				}
			],

			language: {
				url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
			},
			order: [[0, 'desc']]
		});

		// Recherche en temps réel
		const $searchInput = $('#search-article');
		const $searchResults = $('#search-results');
		let searchTimeout;

		$searchInput.on('input', function() {
			const query = $(this).val().trim();

			// Effacer le timeout précédent
			clearTimeout(searchTimeout);

			if (query.length < 2) {
				$searchResults.removeClass('show').html('');
				return;
			}

			// Définir un délai avant d'envoyer la requête
			searchTimeout = setTimeout(() => {
				$.ajax({
					url: '/api/articles/search',
					method: 'GET',
					data: { q: query },
					dataType: 'json',
					success: function(response) {
						if (response.results && response.results.length > 0) {
							let html = '';

							response.results.forEach(article => {
								html += `
									${article.title}
									${article.categories.join(', ')}
								`;
							});

							$searchResults.html(html).addClass('show');
						} else {
							$searchResults.html('Aucun résultat trouvé').addClass('show');
						}
					}
				});
			}, 300);
		});

		// Cliquer sur un résultat de recherche
		$(document).on('click', '.search-item', function() {
			const articleId = $(this).data('id');
			if (articleId) {
				window.location.href = `/article/${articleId}`;
			}
		});

		// Cacher les résultats quand on clique ailleurs
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.search-container').length) {
				$searchResults.removeClass('show');
			}
		});
	});