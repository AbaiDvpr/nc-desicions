import store from '@/store/store';

export default function notification(centrifugo, { name, token }) {
    const notification = centrifugo.newSubscription(
        name, { token }
    );

    // React channel real-time publications
    notification.on('publication', function(ctx) {
        store.dispatch('notification/addItem', ctx.data);
    });

    // Trigger subscribe process
    notification.subscribe();
}
