fetch('https://api.example.com/recipes/123')
	.then((response) => response.text())
	.then((structuredDataText) => {
		const script = document.createElement('script');
		script.setAttribute('type', 'application/ld+json');
		script.textContent = structuredDataText;
		document.head.appendChild(script);
	});
