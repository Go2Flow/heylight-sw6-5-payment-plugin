const svgFiles = import.meta.glob('./svg/*.svg', { eager: true, query: '?raw', import: 'default' });

export default Object.entries(svgFiles).map(([path, svgContent]) => {
    const componentName = path.split('/').pop().replace('.svg', '');

    return {
        name: componentName,
        template: '<span class="heylight-icon" v-html="svgContent"></span>',
        data() {
            return { svgContent };
        },
    };
});
